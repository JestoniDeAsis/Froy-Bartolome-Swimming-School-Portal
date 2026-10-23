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

    public const STATUSES = ['Active', 'Held', 'Inactive'];

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

    /** GET /coaches/data — coaches + the course list for the checkboxes. */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $coaches = DB::table(self::USERS_TABLE . ' as u')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.' . self::USER_PK)
            ->leftJoin(self::STATUS_TABLE . ' as st', 'st.' . self::STATUS_ID_COL, '=', 'u.' . self::STATUS_COL)
            ->where('u.' . self::ROLE_COL, self::ROLE_COACH)
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get([
                'u.' . self::USER_PK . ' as id',
                'u.username',
                'u.email_address',
                'u.' . self::HANDLES_ATHLETES_COL . ' as handles_athletes',
                'st.' . self::STATUS_NAME_COL . ' as status',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
                'p.phone_number',
            ]);

        $assigned = DB::table(self::COACH_COURSES_TABLE)
            ->whereIn('coach_id', $coaches->pluck('id'))
            ->get(['coach_id', 'course_id'])
            ->groupBy('coach_id');

        $courses = DB::table(self::COURSES_TABLE)
            ->orderBy(self::COURSE_ID_COL)
            ->get([self::COURSE_ID_COL . ' as id', self::COURSE_NAME_COL . ' as name'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values();

        return response()->json([
            'success' => true,
            'coaches' => $coaches->map(fn ($c) => [
                'id' => $c->id,
                'username' => $c->username,
                'email_address' => $c->email_address,
                'status' => $c->status ?: 'Active',
                'handles_athletes' => (bool) $c->handles_athletes,
                'first_name' => $c->first_name,
                'middle_name' => $c->middle_name,
                'last_name' => $c->last_name,
                'name_suffix' => $c->name_suffix,
                'phone_number' => $c->phone_number,
                'course_ids' => ($assigned[$c->id] ?? collect())->pluck('course_id')->map(fn ($v) => (int) $v)->values(),
            ])->values(),
            'courses' => $courses,
        ]);
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

    /** PUT /coaches/{id} — status, course assignment and the "handles athletes" option. */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'handles_athletes' => ['sometimes', 'boolean'],   // left unchanged when not sent
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['integer', 'distinct', 'exists:' . self::COURSES_TABLE . ',' . self::COURSE_ID_COL],
        ], [
            'courses.required' => 'Assign at least one course.',
            'courses.min' => 'Assign at least one course.',
        ]);

        $isCoach = DB::table(self::USERS_TABLE)
            ->where(self::USER_PK, $id)
            ->where(self::ROLE_COL, self::ROLE_COACH)
            ->exists();

        if (!$isCoach) {
            return response()->json(['success' => false, 'message' => 'Coach not found.'], 404);
        }

        $statusId = DB::table(self::STATUS_TABLE)
            ->where(self::STATUS_NAME_COL, $data['status'])
            ->value(self::STATUS_ID_COL);

        if ($statusId === null) {
            return response()->json(['success' => false, 'message' => 'Unknown status.'], 422);
        }

        DB::transaction(function () use ($id, $data, $statusId) {
            $changes = [self::STATUS_COL => $statusId];

            if (array_key_exists('handles_athletes', $data)) {
                $changes[self::HANDLES_ATHLETES_COL] = $data['handles_athletes'] ? 1 : 0;
            }

            DB::table(self::USERS_TABLE)->where(self::USER_PK, $id)->update($changes);

            DB::table(self::COACH_COURSES_TABLE)->where('coach_id', $id)->delete();
            DB::table(self::COACH_COURSES_TABLE)->insert(
                collect($data['courses'])->unique()->map(fn ($courseId) => [
                    'coach_id' => $id,
                    'course_id' => $courseId,
                ])->all()
            );
        });

        return response()->json(['success' => true, 'message' => 'Coach updated.']);
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