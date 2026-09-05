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

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username"
                               placeholder="Enter your username" autocomplete="username"
                               value="{{ old('username') }}" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" autocomplete="current-password" required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="showPassword">
                        <label class="form-check-label" for="showPassword">Show Password</label>
                    </div>

                    <div class="text-end mb-3">
                        <a href="{{ url('forgot-password') }}" class="text-decoration-none">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
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
    const checkbox = document.getElementById("showPassword");
    const password = document.getElementById("password");
    checkbox.addEventListener("change", function () {
        password.type = this.checked ? "text" : "password";
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