<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ForgotUsernameController extends Controller
{
    // These limits are separate from the verification-code and password-reset limits.
    private const DAILY_MAX        = 3;    // username emails per email address, per day
    private const COOLDOWN_SECONDS = 30;   // short wait between sends (set to 0 to remove)
    private const IP_HOURLY_MAX    = 10;   // requests per IP address, per hour (stops email guessing)

    // Column names in tbl_users. Change these if your table uses different names.
    private const EMAIL_COLUMN    = 'email_address';
    private const USERNAME_COLUMN = 'username';
    private const NAME_COLUMN     = 'first_name';

    public function show()
    {
        return view('auth.forgot-username');
    }

    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_address' => ['required', 'email:rfc', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = mb_strtolower(trim($request->input('email_address')));
        $hash  = sha1($email);

        $ipKey       = 'forgot-username:ip:' . $request->ip();
        $dayKey      = "forgot-username:day:{$hash}";
        $cooldownKey = "forgot-username:cooldown:{$hash}";

        // 1. Per-IP limit on every request, so the form cannot be used to guess emails.
        if (RateLimiter::tooManyAttempts($ipKey, self::IP_HOURLY_MAX)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return response()->json([
                'success'           => false,
                'reason'            => 'limit',
                'message'           => 'Too many attempts. Please try again in ' . $this->humanWait($seconds) . '.',
                'next_wait_seconds' => $seconds,
            ], 429);
        }

        // 2. Check that the email has an account FIRST.
        $user = User::whereRaw('LOWER(`' . self::EMAIL_COLUMN . '`) = ?', [$email])->first();

        if (! $user) {
            RateLimiter::hit($ipKey, 3600);

            return response()->json([
                'success' => false,
                'reason'  => 'not_found',
                'message' => 'No account is registered with that email address.',
            ], 404);
        }

        // 3. Daily cap: 3 emails per address per day.
        if (RateLimiter::tooManyAttempts($dayKey, self::DAILY_MAX)) {
            $seconds = RateLimiter::availableIn($dayKey);

            return response()->json([
                'success'           => false,
                'reason'            => 'limit',
                'message'           => 'You have reached the limit of ' . self::DAILY_MAX . ' username emails per day. Please try again in ' . $this->humanWait($seconds) . '.',
                'next_wait_seconds' => $seconds,
                'remaining'         => 0,
                'daily_max'         => self::DAILY_MAX,
            ], 429);
        }

        // 4. Short cooldown so a double click does not use up the daily emails.
        if (self::COOLDOWN_SECONDS > 0 && RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);

            return response()->json([
                'success'           => false,
                'reason'            => 'cooldown',
                'message'           => 'Please wait ' . $this->humanWait($seconds) . ' before sending again.',
                'next_wait_seconds' => $seconds,
                'remaining'         => RateLimiter::remaining($dayKey, self::DAILY_MAX),
                'daily_max'         => self::DAILY_MAX,
            ], 429);
        }

        // 5. Send the email. Only a send that really went out is counted.
        try {
            Mail::send('emails.username-reminder', [
                'name'     => $user->{self::NAME_COLUMN} ?: 'there',
                'username' => $user->{self::USERNAME_COLUMN},
            ], function ($message) use ($email) {
                $message->to($email)->subject('Your FBSS Portal username');
            });
        } catch (\Throwable $e) {
            Log::error('Forgot-username email failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'We could not send the email right now. Please try again in a moment.',
            ], 500);
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($dayKey, 86400);

        if (self::COOLDOWN_SECONDS > 0) {
            RateLimiter::hit($cooldownKey, self::COOLDOWN_SECONDS);
        }

        $remaining = RateLimiter::remaining($dayKey, self::DAILY_MAX);

        return response()->json([
            'success'           => true,
            'message'           => 'We sent your username to ' . $email . '.',
            'remaining'         => $remaining,
            'daily_max'         => self::DAILY_MAX,
            'limit_reached'     => $remaining === 0,
            'next_wait_seconds' => $remaining === 0
                ? RateLimiter::availableIn($dayKey)
                : self::COOLDOWN_SECONDS,
        ]);
    }

    private function humanWait(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int) ceil($seconds / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        return $seconds . ' second' . ($seconds === 1 ? '' : 's');
    }
}