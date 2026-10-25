@extends('layouts.app')

@section('title', 'FBSS Portal - Verify Device')

@section('styles')
<style>
    html, body { height: 100%; }
    .login-page { min-height: 100vh; }
    .login-right { background-color: #fff; }
    .login-card { border: none; box-shadow: none; }
    .login-title { font-size: 2rem; font-weight: 600; }
    .login-form-wrap { width: 100%; max-width: 480px; margin-left: auto; margin-right: auto; }
    .verify-code-input { letter-spacing: .5rem; text-align: center; font-size: 1.5rem; font-weight: 600; }
    /* The wide spacing is for the typed digits only, not the hint text. */
    .verify-code-input::placeholder { letter-spacing: normal; font-size: 1rem; font-weight: 400; }
    @media (max-width: 1199.98px) { .login-right { min-height: 100vh; } }
</style>
@endsection

@section('content')
<div class="login-page row g-0">

    @include('auth.partials.left-panel')

    <div class="login-right col-12 col-xl-6 d-flex align-items-center justify-content-center">
        <div class="login-form-wrap px-4 py-4">
            <div class="card login-card p-4">

                <h2 class="login-title text-center mb-1">Verify this device</h2>
                <p class="text-center text-muted mb-4">
                    We don't recognize this browser. Enter the 6-digit code we sent to
                    <strong>{{ $maskedEmail }}</strong>. It expires in {{ $ttl }} minutes.
                </p>

                <form method="POST" action="{{ url('login/verify-device') }}" id="verifyForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="verification_code">Verification code</label>
                        <input type="text" class="form-control verify-code-input" id="verification_code"
                               name="verification_code" maxlength="6" inputmode="numeric"
                               autocomplete="one-time-code" placeholder="Enter 6-digit code" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="verifyButton">Verify and log in</button>
                </form>

                <form method="POST" action="{{ url('login/verify-device/resend') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-link w-100 text-decoration-none">Resend code</button>
                </form>

                <hr>

                <p class="text-center mb-0">
                    <a href="{{ route('login') }}" class="text-decoration-none">Back to login</a>
                </p>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const codeInput = document.getElementById("verification_code");

    codeInput.addEventListener("input", function () {
        codeInput.value = codeInput.value.replace(/\D/g, "").slice(0, 6);
    });

    document.getElementById("verifyForm").addEventListener("submit", function (event) {
        if (codeInput.value.length !== 6) {
            event.preventDefault();
            showToast("Enter the 6-digit code.", "danger");
            codeInput.focus();
            return;
        }

        const button = document.getElementById("verifyButton");
        button.disabled = true;
        button.textContent = "Verifying...";
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) location.reload();
    });
</script>

@if (session('status'))
<script>
    showToast(@json(session('status')), "success");
</script>
@endif

@if ($errors->any())
<script>
    showToast(@json($errors->first()), "danger");
</script>
@endif
@endsection