<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\UserProfile;

class ProfileController extends Controller
{
    /**
     * Persist changes to the logged-in user's profile.
     * Called via fetch() from pages.shared.profile's inline JS, after the
     * user confirms their current password in the "Confirm Your Password"
     * modal.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        if (!Hash::check($request->validated()['current_password'], Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password.',
            ], 422);
        }

        $profileData = collect($request->validated())
            ->except('current_password')
            ->toArray();

        UserProfile::updateOrCreate(
            ['user_id' => Auth::user()->user_id],
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
}