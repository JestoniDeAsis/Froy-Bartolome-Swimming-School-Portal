<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS    = 5;    // failed tries per username + IP
    private const MAX_IP_ATTEMPTS = 20;   // failed tries per IP, any username
    private const LOCKOUT_SECONDS = 300;  // 5 minutes

    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

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
        if ((int) $user->status_id !== 1) {
            $message = match ((int) $user->status_id) {
                2 => 'Your account is currently on hold. Please contact the administrator.',
                3 => 'Your account has been deactivated. Please contact the administrator.',
                default => 'Your account status does not allow login. Please contact the administrator.',
            };

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $message]);
        }

        // Log in only active accounts.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}