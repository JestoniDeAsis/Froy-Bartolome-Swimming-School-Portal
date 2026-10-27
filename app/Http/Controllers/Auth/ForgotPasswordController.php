<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Services\VerificationCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    public function __construct(protected VerificationCodeService $codes) {}

    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.forgot_password');
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email_address' => ['required', 'email', 'exists:tbl_users,email_address'],
        ], [
            'email_address.exists' => 'No account was found with this email address.',
        ]);

        $email = $request->email_address;

        $check = $this->codes->canSend($email, 'password_reset');

        if (!$check['allowed']) {
            $message = $check['reason'] === 'daily_limit'
                ? 'You\'ve reached the daily limit for reset codes. Please try again tomorrow.'
                : 'Please wait ' . ceil($check['wait_seconds'] / 60) . ' minute(s) before requesting another code.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'reason' => $check['reason'],
            ], 429);
        }

        $code = $this->codes->generate($email, 'password_reset');

        Mail::to($email)->send(new PasswordResetCodeMail($code));

        $request->session()->put('password-reset_email', $email);

        return response()->json([
            'success' => true,
            'next_wait_seconds' => $this->codes->nextCooldownSeconds($email, 'password_reset'),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['verification_code' => ['required', 'digits:6']]);

        $email = $request->session()->get('password-reset_email');

        // Too many wrong codes: tell the user how long to wait.
        if ($email && $this->codes->isLockedOut($email, 'password_reset')) {
            $minutes = max(1, (int) ceil($this->codes->lockoutSeconds($email, 'password_reset') / 60));

            return response()->json([
                'success' => false,
                'message' => "Too many incorrect codes. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
            ]);
        }

        if (!$email || !$this->codes->verify($email, 'password_reset', $request->verification_code)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired verification code.']);
        }

        return response()->json(['success' => true]);
    }

    public function updatePassword(Request $request)
    {
        $email = $request->session()->get('password-reset_email');

        if (!$email || !$this->codes->isVerified($email, 'password_reset')) {
            return response()->json([
                'success' => false,
                'message' => 'Your session has expired. Please start over.',
            ]);
        }

        $request->validate([
            'new_password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'confirm_password.same' => 'Passwords do not match.',
        ]);

        DB::table('tbl_users')
            ->where('email_address', $email)
            ->update(['password' => Hash::make($request->new_password)]);

        $this->codes->clear($email, 'password_reset');
        $request->session()->forget('password-reset_email');

        return response()->json(['success' => true]);
    }
}