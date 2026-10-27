<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

use App\Models\UserProfile;

class ProfileController extends Controller
{
    private const MAX_PASSWORD_ATTEMPTS = 5;    // wrong current-password tries before lockout
    private const LOCKOUT_SECONDS       = 300;  // 5 minutes

    /**
     * Persist changes to the logged-in user's profile.
     * Called via fetch() from pages.shared.profile's inline JS, after the
     * user confirms their current password in the "Confirm Your Password"
     * modal.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        // One counter per user. If you add the same check to
        // ChangePasswordController@verifyCurrent, reuse this exact key
        // so both endpoints share a single limit.
        $key = 'profile-password|' . $user->user_id;

        // Checked before the password is compared, so a locked-out
        // user cannot keep guessing.
        if (RateLimiter::tooManyAttempts($key, self::MAX_PASSWORD_ATTEMPTS)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return response()->json([
                'success' => false,
                'message' => "Too many incorrect password attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
            ], 429);
        }

        if (!Hash::check($request->validated()['current_password'], $user->password)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            return response()->json([
                'success' => false,
                'message' => 'Incorrect password.',
            ], 422);
        }

        // Correct password: reset the counter.
        RateLimiter::clear($key);

        $profileData = collect($request->validated())
            ->except('current_password')
            ->toArray();

        UserProfile::updateOrCreate(
            ['user_id' => $user->user_id],
            $profileData
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function data(int $roleId, $profile): array
    {
        $roleClasses = [
            1 => 'role-badge-admin',
            2 => 'role-badge-coach',
            3 => 'role-badge-student',
            4 => 'role-badge-athlete',
        ];

        return [
            'currentUser'         => Auth::user(),
            'roleLabel'           => DB::table('tbl_roles')->where('role_id', $roleId)->value('role_name') ?? 'N/A',
            'roleClass'           => $roleClasses[$roleId] ?? '',
            'contactSectionLabel' => in_array($roleId, [3, 4]) ? 'Parent / Guardian' : 'Emergency Contact',
            'profile'             => $profile,
        ];
    }

    public function verifyCurrent(Request $request): JsonResponse   
    {
        $request->validate(['current_password' => ['required', 'string']]);

        $user = Auth::user();
        $key  = 'profile-password|' . $user->user_id;   // same key as ProfileController

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return response()->json([
                'success' => false,
                'message' => "Too many incorrect password attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
            ], 429);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            RateLimiter::hit($key, 300);

            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        RateLimiter::clear($key);

        return response()->json(['success' => true]);
    }
}