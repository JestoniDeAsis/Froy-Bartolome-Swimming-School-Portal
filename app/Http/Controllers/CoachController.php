<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoachRequest;
use App\Mail\CoachAccountMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoachController extends Controller
{
    /* ---------------------------------------------------------
     * ASSUMPTIONS — confirm or change these to match the schema.
     * Everything schema-dependent is in this block on purpose.
     * --------------------------------------------------------- */
    public const ROLE_ADMIN = 1;   // from config/role_menus.php
    public const ROLE_COACH = 2;   // from config/role_menus.php
    public const MIN_COACH_AGE = 18;

    public const USERS_TABLE   = 'tbl_users';
    public const USER_PK       = 'user_id';     // confirmed (HomeController joins u.user_id)
    public const ROLE_COL      = 'role_id';     // confirmed (Auth::user()->role_id)
    // Status is a lookup: tbl_users.<STATUS_COL> -> tbl_status.<STATUS_ID_COL>
    public const STATUS_COL      = 'status_id';   // ASSUMED: FK column on tbl_users (default 1 = Active)
    public const STATUS_TABLE    = 'tbl_status';  // confirmed by you
    public const STATUS_ID_COL   = 'status_id';   // ASSUMED
    public const STATUS_NAME_COL = 'status_name'; // ASSUMED

    public const COURSES_TABLE = 'tbl_courses'; // confirmed
    public const COURSE_ID_COL = 'course_id';   // confirmed
    public const COURSE_NAME_COL = 'course_name'; // confirmed

    public const COACH_COURSES_TABLE = 'tbl_coach_courses'; // ASSUMED: pivot (coach_id, course_id)

    // A coach can also handle athletes (role 4) on top of their courses.
    // 0/1 flag on tbl_users, only meaningful for coaches. Default 0.
    public const HANDLES_ATHLETES_COL = 'handles_athletes';

    // Kept for reference only: validation reads the names straight from tbl_status
    // so a renamed status can never drift out of sync with this file again.
    public const STATUSES = ['Active', 'On hold', 'Deactivated'];

    /* ---- User management (this page lists every role except admin) ---- */
    public const ROLE_STUDENT        = 3;
    public const ROLE_ATHLETE        = 4;
    public const ROLE_APPLICANT      = 5;   // default role of a new registration
    public const ROLE_FORMER_ATHLETE = 6;
    public const ROLES_TABLE         = 'tbl_roles';    // role_id, role_name
    // Student course per user. Same table AdminAttendanceController reads (enrollment_id, user_id, course_id).
    public const STUDENTS_TABLE      = \App\Models\Attendance::ENROLLMENTS_TABLE;

    public const TABS = ['coaches', 'students', 'athletes', 'applicants'];

    // Roles shown on each tab. Admins are never listed.
    private const TAB_ROLES = [
        'coaches'    => [self::ROLE_COACH],
        'students'   => [self::ROLE_STUDENT],
        'athletes'   => [self::ROLE_ATHLETE, self::ROLE_FORMER_ATHLETE],
        'applicants' => [self::ROLE_APPLICANT],
    ];

    // Same name rule as registration and profile: letters (including ñ, é, etc.),
    // spaces, periods, apostrophes, and hyphens. Must start with a letter.
    public const NAME_REGEX = '/^\p{L}[\p{L} .\'’-]*$/u';

    public static function userIsAdmin($user): bool
    {
        return $user && (int) ($user->{self::ROLE_COL} ?? 0) === self::ROLE_ADMIN;
    }

    private function denyUnlessAdmin(Request $request): ?JsonResponse
    {
        return self::userIsAdmin($request->user())
            ? null
            : response()->json(['success' => false, 'message' => 'You are not allowed to do that.'], 403);
    }

    private function applyTabRoles($query, string $tab, string $roleColumn)
    {
        return $query->whereIn($roleColumn, self::TAB_ROLES[$tab]);
    }

    /**
     * GET /coaches/data?tab=coaches|students|athletes|applicants
     *
     * Users of one tab (admins are never listed), plus what the page needs to
     * draw itself: the course list (coach checkboxes), the status names from
     * tbl_status (filter + edit dropdown) and a head-count per tab.
     */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $tab = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
        ])['tab'] ?? 'coaches';

        $query = DB::table(self::USERS_TABLE . ' as u')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.' . self::USER_PK)
            ->leftJoin(self::STATUS_TABLE . ' as st', 'st.' . self::STATUS_ID_COL, '=', 'u.' . self::STATUS_COL)
            ->leftJoin(self::ROLES_TABLE . ' as r', 'r.role_id', '=', 'u.' . self::ROLE_COL);

        $users = $this->applyTabRoles($query, $tab, 'u.' . self::ROLE_COL)
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get([
                'u.' . self::USER_PK . ' as id',
                'u.username',
                'u.email_address',
                'u.' . self::ROLE_COL . ' as role_id',
                'r.role_name',
                'u.' . self::HANDLES_ATHLETES_COL . ' as handles_athletes',
                'st.' . self::STATUS_NAME_COL . ' as status',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
                'p.phone_number',
            ]);

        $ids = $users->pluck('id');

        // Coaches: the courses they are assigned to.
        $assigned = $tab === 'coaches'
            ? DB::table(self::COACH_COURSES_TABLE)
                ->whereIn('coach_id', $ids)
                ->get(['coach_id', 'course_id'])
                ->groupBy('coach_id')
            : collect();

        // Students: the course(s) they are enrolled in.
        $studentCourses = $tab === 'students'
            ? DB::table(self::STUDENTS_TABLE . ' as s')
                ->join(self::COURSES_TABLE . ' as c', 'c.' . self::COURSE_ID_COL, '=', 's.course_id')
                ->whereIn('s.user_id', $ids)
                ->orderBy('c.' . self::COURSE_ID_COL)
                ->get(['s.user_id', 'c.' . self::COURSE_NAME_COL . ' as name', 's.graduation_at', 's.dropped_at'])
                ->groupBy('user_id')
            : collect();

        $courses = DB::table(self::COURSES_TABLE)
            ->orderBy(self::COURSE_ID_COL)
            ->get([self::COURSE_ID_COL . ' as id', self::COURSE_NAME_COL . ' as name'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values();

        $statuses = DB::table(self::STATUS_TABLE)
            ->orderBy(self::STATUS_ID_COL)
            ->pluck(self::STATUS_NAME_COL)
            ->values();

        // Head-count per tab (shown on the tab buttons; "Others" hides itself at 0).
        $roleCounts = DB::table(self::USERS_TABLE)
            ->select(self::ROLE_COL . ' as role_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy(self::ROLE_COL)
            ->pluck('total', 'role_id');

        $counts = [];

        foreach (self::TAB_ROLES as $name => $roleIds) {
            $counts[$name] = (int) collect($roleIds)->sum(fn ($roleId) => $roleCounts[$roleId] ?? 0);
        }

        return response()->json([
            'success' => true,
            'tab' => $tab,
            'counts' => $counts,
            'statuses' => $statuses,
            'courses' => $courses,
            'users' => $users->map(fn ($u) => [
                'id' => (int) $u->id,
                'username' => $u->username,
                'email_address' => $u->email_address,
                'role_id' => (int) $u->role_id,
                'role_name' => $u->role_name,
                'status' => $u->status ?: 'Active',
                'handles_athletes' => (bool) $u->handles_athletes,
                'first_name' => $u->first_name,
                'middle_name' => $u->middle_name,
                'last_name' => $u->last_name,
                'name_suffix' => $u->name_suffix,
                'phone_number' => $u->phone_number,
                'course_ids' => ($assigned[$u->id] ?? collect())->pluck('course_id')->map(fn ($v) => (int) $v)->values(),
                'course_names' => ($studentCourses[$u->id] ?? collect())->pluck('name')->unique()->implode(', '),
                'enrollment_status' => $tab === 'students'
                    ? $this->enrollmentStatus($studentCourses[$u->id] ?? collect())
                    : null,
            ])->values(),
        ]);
    }

    /**
     * GET /coaches/{id}/profile — read-only details for the "View Info" popup
     * (used in emergencies). Admins are never shown, same as the list.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $u = DB::table(self::USERS_TABLE . ' as u')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.' . self::USER_PK)
            ->leftJoin(self::STATUS_TABLE . ' as st', 'st.' . self::STATUS_ID_COL, '=', 'u.' . self::STATUS_COL)
            ->leftJoin(self::ROLES_TABLE . ' as r', 'r.role_id', '=', 'u.' . self::ROLE_COL)
            ->where('u.' . self::USER_PK, $id)
            ->whereIn('u.' . self::ROLE_COL, collect(self::TAB_ROLES)->flatten()->all())
            ->first([
                'u.username',
                'u.email_address',
                'r.role_name',
                'st.' . self::STATUS_NAME_COL . ' as status',
                'p.birthdate',
                'p.sex',
                'p.phone_number',
                'p.address',
                'p.parent_guardian_fullname',
                'p.parent_guardian_relationship',
                'p.parent_guardian_phone_number',
                'p.account_handler',
                'u.' . self::ROLE_COL . ' as role_id',
                'u.athlete_since',
                'u.athlete_until',
            ]);

        if (!$u) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $birth = $u->birthdate ? \Illuminate\Support\Carbon::parse($u->birthdate) : null;

        $roleId = (int) $u->role_id;
        $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('F j, Y') : null;

        // Athletes and former (retired) athletes: the dates they were athletes.
        $athlete = in_array($roleId, [self::ROLE_ATHLETE, self::ROLE_FORMER_ATHLETE], true)
            ? [
                'since' => $fmt($u->athlete_since),
                'until' => $fmt($u->athlete_until),
                'retired' => $roleId === self::ROLE_FORMER_ATHLETE,
            ]
            : null;

        // Students: every enrollment with its state (Enrolled / Dropped / Graduated).
        $enrollments = $roleId === self::ROLE_STUDENT
            ? DB::table(self::STUDENTS_TABLE . ' as s')
                ->join(self::COURSES_TABLE . ' as c', 'c.' . self::COURSE_ID_COL, '=', 's.course_id')
                ->where('s.user_id', $id)
                ->orderBy('s.enrollment_id')
                ->get([
                    'c.' . self::COURSE_NAME_COL . ' as course',
                    's.graduation_at',
                    's.dropped_at',
                    's.drop_reason',
                ])
                ->map(function ($e) use ($fmt) {
                    $state = $e->graduation_at ? 'Graduated' : ($e->dropped_at ? 'Dropped' : 'Enrolled');

                    return [
                        'course' => $e->course,
                        'state' => $state,
                        'date' => $fmt($state === 'Graduated' ? $e->graduation_at : $e->dropped_at),
                        'reason' => $state === 'Dropped' ? ($e->drop_reason ?: null) : null,
                    ];
                })
                ->values()
                ->all()
            : [];

        return response()->json([
            'success' => true,
            'profile' => [
                'username' => $u->username,
                'email_address' => $u->email_address,
                'role_name' => $u->role_name,
                'status' => $u->status ?: 'Active',
                'birthdate' => $birth?->format('F j, Y'),
                'age' => $birth?->age,
                'sex' => $u->sex,
                'phone_number' => $u->phone_number,
                'address' => $u->address,
                'emergency_name' => $u->parent_guardian_fullname,
                'emergency_relationship' => $u->parent_guardian_relationship,
                'emergency_phone' => $u->parent_guardian_phone_number,
                'account_handler' => $u->account_handler === 'self' ? 'Self' : 'Parent / guardian',
                'athlete' => $athlete,
                'enrollments' => $enrollments,
            ],
        ]);
    }

    /**
     * One status per student for the Enrollment filter. Priority: a student who is
     * enrolled somewhere right now is "Enrolled" (even if dropped from another course);
     * otherwise "Dropped" if an unfinished enrollment was dropped; otherwise "Graduated".
     */
    private function enrollmentStatus(\Illuminate\Support\Collection $rows): string
    {
        $open = fn ($r) => $r->graduation_at === null;

        if ($rows->contains(fn ($r) => $open($r) && $r->dropped_at === null)) {
            return 'Enrolled';
        }

        if ($rows->contains(fn ($r) => $open($r) && $r->dropped_at !== null)) {
            return 'Dropped';
        }

        return $rows->isNotEmpty() ? 'Graduated' : 'Not enrolled';
    }

    /** POST /coaches/account-preview — generated username + password shown in step 3. */
    public function accountPreview(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
        ], [
            'first_name.regex' => 'First name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'last_name.regex' => 'Last name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'last_name.min' => 'Last name must be at least 2 characters.',
        ]);

        $username = $this->generateUsername($data['first_name'], $data['last_name']);

        if ($username === '') {
            return response()->json([
                'success' => false,
                'message' => 'The first and last name must contain letters or numbers so a username can be generated.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'username' => $username,
            'password' => $this->generatePassword(),
        ]);
    }

    /** POST /coaches — admin creates a coach account. No email-code step. */
    public function store(StoreCoachRequest $request): JsonResponse
    {
        $v = $request->validated();
        $handlesAthletes = $request->boolean('handles_athletes') ? 1 : 0;

        $userId = DB::transaction(function () use ($v, $handlesAthletes) {
            $userId = DB::table(self::USERS_TABLE)->insertGetId([
                'username' => $v['username'],
                'password' => Hash::make($v['password']),
                'email_address' => $v['email_address'],
                self::ROLE_COL => self::ROLE_COACH,
                self::HANDLES_ATHLETES_COL => $handlesAthletes,
            ]);

            DB::table('tbl_user_profile')->insert([
                'user_id' => $userId,
                'first_name' => $v['first_name'],
                'middle_name' => $v['middle_name'] ?? null,
                'last_name' => $v['last_name'],
                'name_suffix' => ($v['name_suffix'] ?? '') !== '' ? $v['name_suffix'] : null,
                'birthdate' => $v['birthdate'] ?? null,
                'sex' => $v['sex'] ?? null,
                'phone_number' => $v['phone_number'] ?? null,
                'address' => $v['address'] ?? null,
                'parent_guardian_fullname' => $v['parent_guardian_fullname'] ?? null,
                'parent_guardian_relationship' => $v['parent_guardian_relationship'] ?? null,
                'parent_guardian_phone_number' => $v['parent_guardian_phone_number'] ?? null,
                'account_handler' => 'self',
            ]);

            DB::table(self::COACH_COURSES_TABLE)->insert(
                collect($v['courses'])->unique()->map(fn ($courseId) => [
                    'coach_id' => $userId,
                    'course_id' => $courseId,
                ])->all()
            );

            return $userId;
        });

        // The account already exists at this point; an email failure must not undo it.
        $mailSent = true;
        try {
            Mail::to($v['email_address'])->send(new CoachAccountMail(
                trim($v['first_name'] . ' ' . $v['last_name']),
                $v['username'],
                $v['password'],
            ));
        } catch (\Throwable $e) {
            $mailSent = false;
            Log::warning('Coach account email failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'id' => $userId,
            'mail_sent' => $mailSent,
            'message' => 'Coach account created.',
        ], 201);
    }

    /**
     * PUT /coaches/{id} — change a user's status. For a coach it also saves the
     * course assignment and the "handles athletes" option. Admin accounts (and
     * your own) cannot be changed from here.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $target = DB::table(self::USERS_TABLE)
            ->where(self::USER_PK, $id)
            ->first([self::ROLE_COL . ' as role_id']);

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $roleId = (int) $target->role_id;

        if ($roleId === self::ROLE_ADMIN || (int) $request->user()->getAuthIdentifier() === $id) {
            return response()->json(['success' => false, 'message' => 'Admin accounts cannot be changed here.'], 403);
        }

        $isCoach = $roleId === self::ROLE_COACH;

        $rules = [
            'status' => ['required', 'string', Rule::exists(self::STATUS_TABLE, self::STATUS_NAME_COL)],
        ];

        if ($isCoach) {
            $rules += [
                'handles_athletes' => ['sometimes', 'boolean'],   // left unchanged when not sent
                'courses' => ['required', 'array', 'min:1'],
                'courses.*' => ['integer', 'distinct', 'exists:' . self::COURSES_TABLE . ',' . self::COURSE_ID_COL],
            ];
        }

        $data = $request->validate($rules, [
            'status.exists' => 'Unknown status.',
            'courses.required' => 'Assign at least one course.',
            'courses.min' => 'Assign at least one course.',
        ]);

        $statusId = DB::table(self::STATUS_TABLE)
            ->where(self::STATUS_NAME_COL, $data['status'])
            ->value(self::STATUS_ID_COL);

        if ($statusId === null) {
            return response()->json(['success' => false, 'message' => 'Unknown status.'], 422);
        }

        DB::transaction(function () use ($id, $data, $statusId, $isCoach) {
            $changes = [self::STATUS_COL => $statusId];

            if ($isCoach && array_key_exists('handles_athletes', $data)) {
                $changes[self::HANDLES_ATHLETES_COL] = $data['handles_athletes'] ? 1 : 0;
            }

            DB::table(self::USERS_TABLE)->where(self::USER_PK, $id)->update($changes);

            if ($isCoach) {
                DB::table(self::COACH_COURSES_TABLE)->where('coach_id', $id)->delete();
                DB::table(self::COACH_COURSES_TABLE)->insert(
                    collect($data['courses'])->unique()->map(fn ($courseId) => [
                        'coach_id' => $id,
                        'course_id' => $courseId,
                    ])->all()
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'User updated.']);
    }

    /**
     * fbss_<first initial>_<surname>, lowercase letters/digits only
     * (e.g. Juan Dela Cruz -> fbss_j_delacruz). A number is appended
     * if the name is already taken.
     */
    private function generateUsername(string $firstName, string $lastName): string
    {
        $clean = fn (string $s): string => strtolower(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($s)));

        $initial = substr($clean($firstName), 0, 1);
        $surname = substr($clean($lastName), 0, 20);

        if ($initial === '' || $surname === '') {
            return '';
        }

        $base = 'fbss_' . $initial . '_' . $surname;
        $username = $base;
        $suffix = 2;

        while (DB::table(self::USERS_TABLE)->where('username', $username)->exists()) {
            $username = $base . $suffix++;
        }

        return $username;
    }

    /**
     * 12 random characters with at least one uppercase, lowercase, digit and
     * special character (matches the portal's password rule). Look-alike
     * characters (I, l, O, 0, 1) are left out so it can be read out loud.
     */
    private function generatePassword(int $length = 12): string
    {
        $sets = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%&*?',
        ];

        $all = implode('', $sets);
        $chars = array_map(fn (string $set): string => $set[random_int(0, strlen($set) - 1)], $sets);

        while (count($chars) < $length) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}