<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
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

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => 'Invalid username or password.',
                ]);
        }

        // Prevent login if the account is not active.
        if ((int) $user->status_id !== 1) {
            $message = match ((int) $user->status_id) {
                2 => 'Your account is currently on hold. Please contact the administrator.',
                3 => 'Your account is inactive. Please contact the administrator.',
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
