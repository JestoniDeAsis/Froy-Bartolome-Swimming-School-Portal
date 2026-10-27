@extends('layouts.app')

@section('title', 'FBSS Portal - Login')

@section('styles')
<style>
    html, body { 
        height: 100%; 
    }
    .login-page { 
        min-height: 100vh; 
    }
    .login-right { 
        background-color: #fff; 
    }
    .login-card { 
        border: none; box-shadow: none; 
    }
    .login-title { 
        font-size: 2rem; font-weight: 600; 
    }
    .login-form-wrap { 
        width: 100%; 
        max-width: 480px; 
        margin-left: auto; 
        margin-right: auto; 
    }
    .toggle-password {
        display: flex;
        align-items: center;
    }
    @media (max-width: 1199.98px) { 
        .login-right { 
            min-height: 100vh; 
        } 
    }
</style>
@endsection

@section('content')
<div class="login-page row g-0">

    @include('auth.partials.left-panel')

    <div class="login-right col-12 col-xl-6 d-flex align-items-center justify-content-center">
        <div class="login-form-wrap px-4 py-4">
            <div class="card login-card p-4">

                <h2 class="login-title text-center mb-1">Welcome Back</h2>
                <p class="text-center text-muted mb-4">Log in to your FBSS account</p>

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Enter your username" maxlength="50" autocomplete="username"
                               autocapitalize="off" spellcheck="false"
                               value="{{ old('username') }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password" maxlength="64" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="password" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <a href="{{ url('forgot-username') }}" class="text-decoration-none">Forgot Username?</a>
                        <a href="{{ url('forgot-password') }}" class="text-decoration-none">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="loginButton">Login</button>
                </form>

                <hr>

                <p class="text-center mb-0">
                    Don't have an account?
                    <a href="{{ url('register') }}" class="text-decoration-none fw-semibold">Register here</a>
                </p>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Set to true if your professor wants pasting blocked on the login password too.
    // Left off by default so password managers and normal typing habits keep working.
    const BLOCK_PASTE_ON_LOGIN = false;

    // ---------- eye icons ----------
    document.querySelectorAll(".toggle-password").forEach(function (button) {
        button.addEventListener("click", function () {
            const input = document.getElementById(button.dataset.target);
            const show = input.type === "password";

            input.type = show ? "text" : "password";
            button.querySelector(".icon-eye").classList.toggle("d-none", show);
            button.querySelector(".icon-eye-off").classList.toggle("d-none", !show);
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");
            button.title = show ? "Hide password" : "Show password";
        });
    });

    // ---------- no copying out of the password field ----------
    let lastClipboardToast = 0;
    const passwordField = document.getElementById("password");
    const blockedEvents = ["copy", "cut", "dragstart"];

    if (BLOCK_PASTE_ON_LOGIN) {
        blockedEvents.push("paste", "drop", "contextmenu");
    }

    blockedEvents.forEach(function (eventName) {
        passwordField.addEventListener(eventName, function (event) {
            event.preventDefault();

            const now = Date.now();
            if (eventName !== "contextmenu" && now - lastClipboardToast > 2000) {
                lastClipboardToast = now;
                showToast(
                    BLOCK_PASTE_ON_LOGIN
                        ? "Copy and paste are disabled on password fields. Please type your password."
                        : "Copying a password is disabled.",
                    "danger"
                );
            }
        });
    });

    // ---------- submit checks ----------
    document.getElementById("loginForm").addEventListener("submit", function (event) {
        const usernameField = document.getElementById("username");
        usernameField.value = usernameField.value.trim();

        if (usernameField.value === "") {
            event.preventDefault();
            showToast("Please enter your username.", "danger");
            usernameField.focus();
            return;
        }

        if (passwordField.value === "") {
            event.preventDefault();
            showToast("Please enter your password.", "danger");
            passwordField.focus();
            return;
        }

        const button = document.getElementById("loginButton");
        button.disabled = true;
        button.textContent = "Logging in...";
    });
</script>

<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) location.reload();
});
</script>

@if (session('status'))
<script>
    showToast(@json(session('status')), "success");
</script>
@endif

@if ($errors->has('login'))
<script>
    showToast(@json($errors->first('login')), "danger");
</script>
@endif
@endsection
