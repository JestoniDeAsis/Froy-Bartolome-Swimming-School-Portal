<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    /**
     * Step 1: the user must prove they know their current password
     * before the new-password fields are ever shown.
     */
    public function verifyCurrent(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ]);
        }

        // Mirrors the password-reset flow's session flag, just keyed off
        // the current password instead of an emailed code.
        $request->session()->put('password-change_verified_at', now());

        return response()->json(['success' => true]);
    }

    /**
     * Step 2: set the new password. Same complexity rule as
     * ForgotPasswordController@updatePassword.
     */
    public function update(Request $request): JsonResponse
    {
        $verifiedAt = $request->session()->get('password-change_verified_at');

        if (!$verifiedAt || now()->diffInMinutes($verifiedAt) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please re-enter your current password.',
            ]);
        }

        $request->validate([
            'new_password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'confirm_password.same' => 'Passwords do not match.',
        ]);

        DB::table('tbl_users')
            ->where('user_id', Auth::user()->user_id)
            ->update(['password' => Hash::make($request->new_password)]);

        $request->session()->forget('password-change_verified_at');

        return response()->json(['success' => true]);
    }
}