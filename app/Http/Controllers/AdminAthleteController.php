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

/**
 * Admin Athletes page (split out of the Registers page).
 *
 *   Athletes      role 4
 *   Candidates    who can be made an athlete: applicants (role 5, never
 *                 enrolled) and graduates who are NOT enrolled right now
 *   Leaderboards  placeholder tab, nothing here yet
 *
 * Rules enforced here (not only in the page):
 *   - only applicants and graduates who are not enrolled now can become athletes
 *   - nobody who is currently enrolled can become an athlete
 *   - revoking sends the person back to Graduates (role 3) if they were ever
 *     enrolled, otherwise back to Applicants (role 5)
 */
class AdminAthleteController extends Controller
{
    private const ADMIN_ROLE_ID     = 1;
    private const STUDENT_ROLE_ID   = 3;
    private const ATHLETE_ROLE_ID   = 4;
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

            if (! in_array($id, $enrolledBefore, true) && (int) $user->role_id !== self::APPLICANT_ROLE_ID) {
                return $this->fail("{$name} is not an applicant or a graduate.");
            }
        }

        DB::table('tbl_users')->whereIn('user_id', $ids)->update(['role_id' => self::ATHLETE_ROLE_ID]);

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
            ->where('role_id', '!=', self::ATHLETE_ROLE_ID)
            ->get()
            ->map(fn ($user) => array_merge($this->personRow($user), ['source' => 'Graduate']));

        return $applicants
            ->concat($graduates)
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