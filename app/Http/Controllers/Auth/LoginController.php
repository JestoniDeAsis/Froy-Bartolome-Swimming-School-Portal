<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewDeviceCodeMail;
use App\Models\User;
use App\Services\DeviceVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS    = 5;    // failed tries per username + IP
    private const MAX_IP_ATTEMPTS = 20;   // failed tries per IP, any username
    private const LOCKOUT_SECONDS = 300;  // 5 minutes

    // Session key: the user who passed the password check but still has to verify this device.
    private const PENDING_KEY = 'pending_device_login';

    public function __construct(protected DeviceVerificationService $devices) {}

    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        // Coming back to the login page abandons any half-finished verification.
        session()->forget(self::PENDING_KEY);

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // One counter per username + IP, and a looser one per IP alone
        // (stops one machine from trying many different usernames).
        $key   = 'login|' . Str::lower($credentials['username']) . '|' . $request->ip();
        $ipKey = 'login-ip|' . $request->ip();

        // Checked before the database is touched, so a blocked client
        // cannot keep guessing passwords.
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)) {

            $seconds = max(RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey));
            $minutes = max(1, (int) ceil($seconds / 60));

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => "Too many failed login attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.',
                ]);
        }

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Only failed passwords count toward the lockout.
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);
            RateLimiter::hit($ipKey, self::LOCKOUT_SECONDS);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => 'Invalid username or password.',
                ]);
        }

        // Correct password: reset this account's counter.
        RateLimiter::clear($key);

        // Prevent login if the account is not active.
        if ($message = $this->inactiveMessage($user)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $message]);
        }

        $userId = (int) $user->user_id;

        // --- TEMPORARILY BYPASS DEVICE VERIFICATION FOR TESTING ---
        // $cookie = $this->devices->remember($request, $userId);
        // return $this->finishLogin($request, $user)->withCookie($cookie);
        // ---------------------------------------------------------

        // A browser we already know: straight in.
        if ($this->devices->isRecognized($request, $userId)) {
            $cookie = $this->devices->touch($request, $userId);

            return $this->finishLogin($request, $user)->withCookie($cookie);
        }

        // An unknown browser: email a code first.
        return $this->startDeviceVerification($request, $user);
    }

    /* =========================================================
     * New-device verification
     * ========================================================= */

    private function startDeviceVerification(Request $request, User $user)
    {
        $userId = (int) $user->user_id;

        if ($this->devices->tooManyWrongCodes($userId)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $this->tooManyWrongCodesMessage($userId)]);
        }

        // A code that is still valid is reused, so logging in twice in a row does not flood the inbox.
        if (!$this->devices->hasActiveCode($userId)) {
            $check = $this->devices->canSend($userId);

            if (!$check['allowed']) {
                return back()
                    ->withInput($request->only('username'))
                    ->withErrors(['login' => $this->waitMessage('Too many verification codes were requested.', $check['wait_seconds'])]);
            }

            if (!$this->sendCode($request, $user)) {
                return back()
                    ->withInput($request->only('username'))
                    ->withErrors(['login' => 'We could not send the verification email. Please try again later.']);
            }
        }

        $request->session()->put(self::PENDING_KEY, ['user_id' => $userId, 'started_at' => now()->timestamp]);

        return redirect()->route('login.verify-device');
    }

    private function sendCode(Request $request, User $user): bool
    {
        $userId = (int) $user->user_id;
        $code = $this->devices->issue($userId);

        try {
            Mail::to($user->email_address)->send(new NewDeviceCodeMail(
                $code,
                (string) $request->ip(),
                (string) $request->userAgent(),
                DeviceVerificationService::CODE_TTL_MINUTES,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::warning('New-device code email failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->devices->forgetCode($userId);

            return false;
        }
    }

    public function showVerifyDevice(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $user = $this->pendingUser($request);

        if (!$user) {
            return $this->verificationExpired();
        }

        return view('auth.verify-device', [
            'maskedEmail' => $this->maskEmail((string) $user->email_address),
            'ttl' => DeviceVerificationService::CODE_TTL_MINUTES,
        ]);
    }

    public function verifyDevice(Request $request)
    {
        $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ], [
            'verification_code.required' => 'Enter the 6-digit code.',
            'verification_code.digits' => 'Enter the 6-digit code.',
        ]);

        $user = $this->pendingUser($request);

        if (!$user) {
            return $this->verificationExpired();
        }

        $userId = (int) $user->user_id;

        if ($this->devices->tooManyWrongCodes($userId)) {
            return $this->verificationLocked($request, $userId);
        }

        if (!$this->devices->check($userId, $request->verification_code)) {
            if ($this->devices->tooManyWrongCodes($userId)) {
                return $this->verificationLocked($request, $userId);
            }

            return back()->withErrors(['verification_code' => 'Invalid or expired verification code.']);
        }

        // The account may have been put on hold while the code was being typed.
        if ($message = $this->inactiveMessage($user)) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('login')->withErrors(['login' => $message]);
        }

        $cookie = $this->devices->remember($request, $userId);

        return $this->finishLogin($request, $user)->withCookie($cookie);
    }

    public function resendDeviceCode(Request $request)
    {
        $user = $this->pendingUser($request);

        if (!$user) {
            return $this->verificationExpired();
        }

        $check = $this->devices->canSend((int) $user->user_id);

        if (!$check['allowed']) {
            return back()->withErrors([
                'verification_code' => $this->waitMessage('Please wait before requesting another code.', $check['wait_seconds']),
            ]);
        }

        if (!$this->sendCode($request, $user)) {
            return back()->withErrors(['verification_code' => 'We could not send the verification email. Please try again later.']);
        }

        $request->session()->put(self::PENDING_KEY, ['user_id' => (int) $user->user_id, 'started_at' => now()->timestamp]);

        return back()->with('status', 'A new verification code was sent.');
    }

    /* =========================================================
     * Helpers
     * ========================================================= */

    private function finishLogin(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_KEY);

        return redirect()->route('home');
    }

    /** The user waiting to verify this device, or null when there is none (or it timed out). */
    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        $expired = !is_array($pending)
            || empty($pending['user_id'])
            || (now()->timestamp - (int) ($pending['started_at'] ?? 0)) > DeviceVerificationService::CODE_TTL_MINUTES * 60;

        if ($expired) {
            $request->session()->forget(self::PENDING_KEY);

            return null;
        }

        return User::where('user_id', (int) $pending['user_id'])->first();
    }

    private function inactiveMessage(User $user): ?string
    {
        if ((int) $user->status_id === 1) {
            return null;
        }

        return match ((int) $user->status_id) {
            2 => 'Your account is currently on hold. Please contact the administrator.',
            3 => 'Your account has been deactivated. Please contact the administrator.',
            default => 'Your account status does not allow login. Please contact the administrator.',
        };
    }

    private function verificationExpired()
    {
        return redirect()->route('login')->withErrors([
            'login' => 'Your verification session expired. Please log in again.',
        ]);
    }

    private function verificationLocked(Request $request, int $userId)
    {
        $request->session()->forget(self::PENDING_KEY);

        return redirect()->route('login')->withErrors(['login' => $this->tooManyWrongCodesMessage($userId)]);
    }

    private function tooManyWrongCodesMessage(int $userId): string
    {
        return $this->waitMessage('Too many incorrect codes.', $this->devices->wrongCodeWaitSeconds($userId));
    }

    private function waitMessage(string $prefix, int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return "{$prefix} Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.';
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2) . str_repeat('*', max(1, mb_strlen($local) - 2)) . '@' . $domain;
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}