<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AthleteRecord;
use Illuminate\Validation\Rule;

/**
 * Admin Athletes page (split out of the Registers page).
 *
 *   Athletes      role 4
 *   Former        role 6: retired athletes (athlete_since / athlete_until keep
 *                 the dates they were athletes, so old attendance stays visible)
 *   Candidates    who can be made an athlete: applicants (role 5, never
 *                 enrolled) and graduates who are NOT enrolled right now
 *   Leaderboards  placeholder tab, nothing here yet
 *
 * Rules enforced here (not only in the page):
 *   - only applicants and graduates who are not enrolled now can become athletes
 *   - nobody who is currently enrolled can become an athlete
 *   - revoking (a mistake) sends the person back to Graduates (role 3) if they
 *     were ever enrolled, otherwise back to Applicants (role 5)
 *   - retiring keeps their history: role 6 and athlete_until = today. A former
 *     athlete can be added back as an athlete later, or restored (undo a
 *     retire mistake) which keeps the original athlete_since
 */
class AdminAthleteController extends Controller
{
    private const ADMIN_ROLE_ID     = 1;
    private const STUDENT_ROLE_ID   = 3;
    private const ATHLETE_ROLE_ID   = 4;
    private const FORMER_ATHLETE_ROLE_ID = 6;
    private const APPLICANT_ROLE_ID = 5;

    /**
     * Called from HomeController's match($page) when $page === 'athletes'.
     * Feeds the Athletes blade: the athlete list and the people the
     * "Add New Athlete" modal can pick from.
     */
    public function data(): array
    {
        return $this->lists();
    }

    /**
     * POST /athletes     body: { user_ids: [1, 2, ...] }
     * Turns applicants and graduates into athletes (role 4). Everyone in the
     * request must be eligible, otherwise nobody is changed:
     *   - not already an athlete
     *   - NOT enrolled in a course right now
     *   - an applicant, or someone who graduated before
     * Replies with the refreshed Athletes / Candidates lists.
     */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:tbl_users,user_id'],
        ]);

        $ids = array_map('intval', $validated['user_ids']);

        $users = User::with('profile')->whereIn('user_id', $ids)->get();

        $enrolledNow = Enrollment::whereIn('user_id', $ids)
            ->whereNull('graduation_at')
            ->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $enrolledBefore = Enrollment::whereIn('user_id', $ids)
            ->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->all();

        foreach ($users as $user) {
            $id = (int) $user->user_id;
            $name = $this->personName($user);

            if ((int) $user->role_id === self::ATHLETE_ROLE_ID) {
                return $this->fail("{$name} is already an athlete.");
            }

            if (in_array($id, $enrolledNow, true)) {
                return $this->fail("{$name} is enrolled in a course right now. Only people who are not enrolled can become athletes.");
            }

            $isFormerAthlete = (int) $user->role_id === self::FORMER_ATHLETE_ROLE_ID;

            if (! in_array($id, $enrolledBefore, true) && (int) $user->role_id !== self::APPLICANT_ROLE_ID && ! $isFormerAthlete) {
                return $this->fail("{$name} is not an applicant, a graduate or a former athlete.");
            }
        }

        // athlete_since = the day they became an athlete. Attendance uses it so
        // the person shows in the Athletes tab only from this date onward.
        DB::table('tbl_users')->whereIn('user_id', $ids)->update([
            'role_id' => self::ATHLETE_ROLE_ID,
            'athlete_since' => Carbon::today()->toDateString(),
            'athlete_until' => null,
        ]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /**
     * POST /athletes/{user}/revoke
     * Takes athlete status away. The person goes back to Graduates if they
     * were ever enrolled (role 3), otherwise back to Applicants (role 5).
     * Replies with the refreshed lists.
     */
    public function revoke(Request $request, int $user): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $roleId = DB::table('tbl_users')->where('user_id', $user)->value('role_id');

        if ($roleId === null) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ((int) $roleId !== self::ATHLETE_ROLE_ID) {
            return $this->fail('This person is not an athlete.');
        }

        $enrolledBefore = Enrollment::where('user_id', $user)->exists();

        DB::table('tbl_users')->where('user_id', $user)->update([
            'role_id' => $enrolledBefore ? self::STUDENT_ROLE_ID : self::APPLICANT_ROLE_ID,
            'athlete_since' => null,
            'athlete_until' => null,
        ]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /**
     * POST /athletes/{user}/retire
     * The athlete stops being an athlete but their history stays: role 6
     * (Former Athlete) and athlete_until = today. Attendance uses the two
     * dates to keep showing them on the days they were athletes.
     * Replies with the refreshed lists.
     */
    public function retire(Request $request, int $user): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $roleId = DB::table('tbl_users')->where('user_id', $user)->value('role_id');

        if ($roleId === null) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ((int) $roleId !== self::ATHLETE_ROLE_ID) {
            return $this->fail('This person is not an athlete.');
        }

        DB::table('tbl_users')->where('user_id', $user)->update([
            'role_id' => self::FORMER_ATHLETE_ROLE_ID,
            'athlete_until' => Carbon::today()->toDateString(),
        ]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /**
     * POST /athletes/{user}/restore
     * Undoes a retire (for a mistake). Former Athlete goes back to Athlete.
     * athlete_since is kept so attendance has no gap; athlete_until is cleared.
     * Not allowed while the person is enrolled in a course.
     */
    public function restore(Request $request, int $user): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $row = DB::table('tbl_users')
            ->where('user_id', $user)
            ->first(['role_id', 'athlete_since']);

        if ($row === null) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ((int) $row->role_id !== self::FORMER_ATHLETE_ROLE_ID) {
            return $this->fail('This person is not a former athlete.');
        }

        $enrolledNow = Enrollment::where('user_id', $user)
            ->whereNull('graduation_at')
            ->exists();

        if ($enrolledNow) {
            return $this->fail('This person is enrolled in a course right now. Only people who are not enrolled can be athletes.');
        }

        DB::table('tbl_users')->where('user_id', $user)->update([
            'role_id' => self::ATHLETE_ROLE_ID,
            'athlete_since' => $row->athlete_since ?? Carbon::today()->toDateString(),
            'athlete_until' => null,
        ]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /* ---------------------------------------------------------
     * Lists
     * --------------------------------------------------------- */

    private function lists(): array
    {
        return [
            'athletes' => $this->athleteRows(),
            'candidates' => $this->candidateRows(),
            'former' => $this->formerRows(),
        ];
    }

    private function athleteRows(): Collection
    {
        return User::with('profile')
            ->where('role_id', self::ATHLETE_ROLE_ID)
            ->get()
            ->map(fn ($user) => $this->personRow($user))
            ->sortBy('lastName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function formerRows(): Collection
    {
        return User::with('profile')
            ->where('role_id', self::FORMER_ATHLETE_ROLE_ID)
            ->get()
            ->map(fn ($user) => array_merge($this->personRow($user), [
                'athleteSince' => $user->athlete_since
                    ? Carbon::parse($user->athlete_since)->format('M-d-Y') : '',
                'retiredOn' => $user->athlete_until
                    ? Carbon::parse($user->athlete_until)->format('M-d-Y') : '',
            ]))
            ->sortBy('lastName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * People the "Add New Athlete" modal can pick from, one row each:
     *   Applicant  role 5 and no enrollment at all
     *   Graduate   graduated at least once, not enrolled now, not an athlete
     * (the same two groups the Registers page lists as Applicants / Graduates).
     */
    private function candidateRows(): Collection
    {
        $applicants = User::with('profile')
            ->where('role_id', self::APPLICANT_ROLE_ID)
            ->whereHas('profile')
            ->whereDoesntHave('enrollments')
            ->get()
            ->map(fn ($user) => array_merge($this->personRow($user), ['source' => 'Applicant']));

        $enrolledNow = Enrollment::whereNull('graduation_at')->pluck('user_id')->unique()->all();

        $graduatedIds = Enrollment::whereNotNull('graduation_at')->pluck('user_id')->unique()->all();

        $graduates = User::with('profile')
            ->whereIn('user_id', $graduatedIds)
            ->whereNotIn('user_id', $enrolledNow)
            ->whereNotIn('role_id', [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID])
            ->get()
            ->map(fn ($user) => array_merge($this->personRow($user), ['source' => 'Graduate']));

        // Retired athletes can be added back as athletes (not while enrolled).
        $formerAthletes = User::with('profile')
            ->where('role_id', self::FORMER_ATHLETE_ROLE_ID)
            ->whereHas('profile')
            ->whereNotIn('user_id', $enrolledNow)
            ->get()
            ->map(fn ($user) => array_merge($this->personRow($user), ['source' => 'Former Athlete']));

        return $applicants
            ->concat($graduates)
            ->concat($formerAthletes)
            ->sortBy('lastName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function personRow(User $user): array
    {
        $profile = $user->profile;

        return array_merge([
            'id' => $user->user_id,
            'lastName' => $profile->last_name ?? '',
            'firstName' => $profile->first_name ?? '',
            'middleName' => $profile->middle_name ?? '',
            'email' => $user->email_address ?? '',
        ], $this->birthFields($profile));
    }

    private function birthFields(?UserProfile $profile): array
    {
        $birthdate = $profile->birthdate ?? null;

        return [
            'birthdate' => $birthdate ? Carbon::parse($birthdate)->format('M-d-Y') : '',
            'age' => $birthdate ? Carbon::parse($birthdate)->age : '',
        ];
    }

    /**
     * GET /athletes/leaderboard?stroke=&distance=
     *
     * Admin read-only leaderboard.
     * Shows each athlete's fastest active record for the selected event.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $v = $request->validate([
            'stroke' => ['required', Rule::in(array_keys(AthleteRecord::STROKES))],
            'distance' => ['required', 'integer', Rule::in(AthleteRecord::DISTANCES)],
        ]);

        $rows = DB::table('tbl_athlete_records as r')
            ->join('tbl_users as u', 'u.user_id', '=', 'r.athlete_id')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->where('u.role_id', self::ATHLETE_ROLE_ID)
            ->where('r.stroke', $v['stroke'])
            ->where('r.distance_m', $v['distance'])
            ->whereNull('r.removed_at')
            ->orderBy('r.time_cs')
            ->orderBy('r.recorded_on')
            ->orderBy('r.record_id')
            ->get([
                'r.athlete_id',
                'r.time_cs',
                'r.recorded_on',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
            ]);

        // Keep each athlete's fastest record for this event.
        $rows = $rows->unique('athlete_id')->values();

        $position = 0;
        $rank = 0;
        $previousTime = null;

        $entries = $rows->map(function ($row) use (&$position, &$rank, &$previousTime) {
            $position++;
            $time = (int) $row->time_cs;

            // Equal times share a rank.
            if ($previousTime === null || $time !== $previousTime) {
                $rank = $position;
            }

            $previousTime = $time;

            $middle = trim((string) ($row->middle_name ?? ''));
            $first = trim((string) ($row->first_name ?? ''));
            $last = trim((string) ($row->last_name ?? ''));
            $suffix = trim((string) ($row->name_suffix ?? ''));

            $name = trim(
                $last . ', ' .
                $first .
                ($middle !== '' ? ' ' . mb_substr($middle, 0, 1) . '.' : '') .
                ($suffix !== '' ? ' ' . $suffix : '')
            );

            return [
                'rank' => $rank,
                'athlete_id' => (int) $row->athlete_id,
                'name' => $name,
                'time' => AthleteRecord::formatTime($time),
                'time_cs' => $time,
                'date' => (string) $row->recorded_on,
            ];
        });

        return response()->json([
            'success' => true,
            'event' => AthleteRecord::eventLabel(
                $v['stroke'],
                (int) $v['distance']
            ),
            'entries' => $entries,
        ]);
    }

    /* ---------------------------------------------------------
     * Helpers
     * --------------------------------------------------------- */

    private function denyUnlessAdmin(): ?JsonResponse
    {
        if ((int) Auth::user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
        }

        return null;
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }

    /** "Last, First" from the profile; falls back to the username. */
    private function personName(User $user): string
    {
        $profile = $user->profile;
        $last = trim((string) ($profile->last_name ?? ''));
        $first = trim((string) ($profile->first_name ?? ''));

        return $last !== '' ? "{$last}, {$first}" : (string) ($user->username ?? 'This person');
    }
}