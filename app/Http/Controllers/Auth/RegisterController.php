<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\RegistrationCodeMail;
use App\Services\VerificationCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function __construct(protected VerificationCodeService $codes) {}

    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'username' => ['required', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'unique:tbl_users,username'],
            'email_address' => ['required', 'email', 'unique:tbl_users,email_address'],
        ]);

        $email = $request->email_address;

        $check = $this->codes->canSend($email, 'registration');

        if (!$check['allowed']) {
            $message = $check['reason'] === 'daily_limit'
                ? 'You\'ve reached the daily limit for verification codes. Please try again tomorrow.'
                : 'Please wait ' . ceil($check['wait_seconds'] / 60) . ' minute(s) before requesting another code.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'reason' => $check['reason'],
            ], 429);
        }

        $code = $this->codes->generate($email, 'registration');

        Mail::to($email)->send(new RegistrationCodeMail($code));

        $request->session()->put('registration_username', $request->username);
        $request->session()->put('registration_email', $email);

        return response()->json([
            'success' => true,
            'next_wait_seconds' => $this->codes->nextCooldownSeconds($email, 'registration'),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('registration_email');

        // Too many wrong codes: tell the user how long to wait.
        if ($email && $this->codes->isLockedOut($email, 'registration')) {
            $minutes = max(1, (int) ceil($this->codes->lockoutSeconds($email, 'registration') / 60));

            return response()->json([
                'success' => false,
                'message' => "Too many incorrect codes. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
            ]);
        }

        if (!$email || !$this->codes->verify($email, 'registration', $request->verification_code)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired verification code.']);
        }

        return response()->json(['success' => true]);
    }

    public function register(RegisterRequest $request)
    {
        $email = $request->session()->get('registration_email');

        if (
            !$email ||
            $email !== $request->email_address ||
            $request->session()->get('registration_username') !== $request->username ||
            !$this->codes->isVerified($email, 'registration')
        ) {
            return back()->withErrors(['registration' => 'Please verify your email before registering.'])->withInput();
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $userId = DB::table('tbl_users')->insertGetId([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'email_address' => $validated['email_address'],
            ]);

            DB::table('tbl_user_profile')->insert([
                'user_id' => $userId,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'name_suffix' => $validated['name_suffix'] ?? null,
                'birthdate' => $validated['birthdate'],
                'sex' => $validated['sex'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'parent_guardian_fullname' => $validated['parent_guardian_fullname'],
                'parent_guardian_relationship' => $validated['parent_guardian_relationship'],
                'parent_guardian_phone_number' => $validated['parent_guardian_phone_number'],
                'account_handler' => $validated['account_handler'],
            ]);
        });

        $this->codes->clear($email, 'registration');
        $request->session()->forget(['registration_username', 'registration_email']);

        return redirect()->route('login')->with('status', 'Account created successfully. You can now log in.');
    }
}