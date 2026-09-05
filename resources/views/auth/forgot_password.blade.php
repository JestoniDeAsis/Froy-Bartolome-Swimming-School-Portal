@extends('layouts.app')

@section('title', 'FBSS Portal - Forgot Password')

@section('styles')
<style>
    html, body { 
        height: 100%; 
    }
    .forgot-password-page {
        min-height: 100vh;
    }
    .forgot-password-right {
        background-color: #fff;
    }
    .forgot-password-form-wrap {
        width: 100%;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }
    .forgot-password-card {
        box-shadow: none;
    }
</style>
@endsection

@section('content')
<div class="forgot-password-page row g-0">

    @include('auth.partials.left-panel')

    <div class="forgot-password-right col-12 col-xl-6 d-flex align-items-center justify-content-center">

        <div class="forgot-password-form-wrap px-4 py-4">

            <div class="card forgot-password-card p-4">

                <div class="text-center text-muted mb-3">
                    <span id="stepText">Step 1 of 3</span>
                </div>

                <form method="POST" action="{{ route('forgot-password.update') }}" id="forgotPasswordForm">
                    @csrf
                    
                    <!-- ================= STEP 1 ================= -->
                    <div class="forgot-step" id="step1">

                        <h2 class="forgot-password-title text-center mb-1">Forgot Password?</h2>
                        <p class="text-center text-muted mb-4">Enter your email address to reset your password.</p>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>

                            <input type="email" 
                                class="form-control" 
                                id="emailAddress" 
                                name="email_address" 
                                placeholder="Enter your email address" 
                                required 
                            >
                        </div>

                        <button type="button" class="btn btn-primary w-100" id="sendCodeButton" onclick="sendPasswordResetCode()">
                            Next
                        </button>

                        <hr>
                        
                        <p class="text-center mb-0">
                            Ready to log in?
                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Log in here</a>
                        </p>

                    </div>

                    <!-- ================= STEP 2 ================= -->
                    <div class="forgot-step d-none" id="step2">
                        <h2 class="forgot-password-title text-center mb-1">Verify Your Email</h2>
                        <p class="text-center text-muted mb-4">Enter the verification code sent to your email.</p>

                        <div class="mb-3">
                            <label class="form-label">
                                Email Address
                            </label>
                            
                            <div class="input-group">

                                <input type="email" class="form-control" id="emailStep2" readonly>

                                <span class="input-group-text text-muted resend-text" id="resendCodeText">
                                    Resend in 60s
                                </span>

                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Verification Code
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="passwordResetCode"
                                name="verification_code"
                                placeholder="Enter 6-digit verification code"
                                maxlength="6"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                required
                            >
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            id="verifyCodeButton"
                            onclick="verifyResetCode()"
                        >
                            Verify
                        </button>
                    </div>

                    <!-- ================= STEP 3 ================= -->
                    <div class="forgot-step d-none" id="step3">

                        <h2 class="forgot-password-title text-center mb-1">Create New Password</h2>
                        <p class="text-center text-muted mb-4">Create a new password for your account.</p>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="newPassword"
                                name="new_password"
                                placeholder="Enter your new password"
                                autocomplete="new-password"
                                required
                            >
                            <div class="form-text mt-2">
                                Password must contain:
                                <ul class="mb-0 ps-3">
                                    <li>At least 8 characters</li>
                                    <li>At least one uppercase letter</li>
                                    <li>At least one lowercase letter</li>
                                    <li>At least one number</li>
                                    <li>At least one special character</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="showNewPassword"
                            >
                            <label class="form-check-label" for="showNewPassword">Show new password</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Confirm your new password"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            id="resetPasswordButton"
                            onclick="updatePassword()"
                        >
                            Reset Password
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentStep = 1;
    let resendTimer = null;
    let resendSeconds = 60;
    let dailyLimitReached = false;

    function validateStep(step) {
        const stepElement = document.getElementById("step" + step);
        const inputs = stepElement.querySelectorAll("input, select, textarea");

        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                return false;
            }
        }

        return true;
    }

    function goToStep(step) {
        if (step > currentStep) {
            if (!validateStep(currentStep)) {
                return;
            }
        }

        const emailAddress = document.getElementById("emailAddress").value.trim();

        if (step === 2) {
            document.getElementById("emailStep2").value = emailAddress;
        }

        document.getElementById("step" + currentStep).classList.add("d-none");
        document.getElementById("step" + step).classList.remove("d-none");

        currentStep = step;
        document.getElementById("stepText").textContent = "Step " + step + " of 3";
    }

    async function sendPasswordResetCode() {
        if (!validateStep(1)) {
            return;
        }

        const emailAddress = document.getElementById("emailAddress").value.trim();
        const button = document.getElementById("sendCodeButton");

        button.disabled = true;
        button.textContent = "Sending...";

        try {
            const formData = new FormData();
            formData.append("email_address", emailAddress);

            const response = await fetch("{{ route('forgot-password.send-code') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (currentStep !== 2) {
                    goToStep(2);
                }

                document.getElementById("passwordResetCode").value = "";
                startResendTimer(data.next_wait_seconds ?? 60);
                document.getElementById("passwordResetCode").focus();
            } else {
                if (data.reason === "daily_limit") {
                    dailyLimitReached = true;
                    lockResend();
                }
                showToast(data.message ?? "Unable to send verification code.", "danger");
            }
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while sending the verification code.", "danger");
        } finally {
            button.disabled = false;
            button.textContent = "Next";
        }
    }

    async function verifyResetCode() {
        const passwordResetCode = document.getElementById("passwordResetCode").value.trim();

        if (!/^\d{6}$/.test(passwordResetCode)) {
            showToast("Please enter a valid 6-digit verification code.", "danger");
            return;
        }

        const button = document.getElementById("verifyCodeButton");
        button.disabled = true;
        button.textContent = "Verifying...";

        try {
            const formData = new FormData();
            formData.append("verification_code", passwordResetCode);

            const response = await fetch("{{ route('forgot-password.verify-code') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                goToStep(3);
                document.getElementById("newPassword").focus();
            } else {
                showToast(data.message ?? "Invalid verification code.", "danger");
            }
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while verifying the code.", "danger");
        } finally {
            button.disabled = false;
            button.textContent = "Verify";
        }
    }

    function lockResend() {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }

        const resendText = document.getElementById("resendCodeText");
        resendText.onclick = null;
        resendText.classList.remove("text-primary", "fw-semibold");
        resendText.classList.add("text-muted");
        resendText.style.cursor = "default";
        resendText.textContent = "Daily limit reached";
    }

    function startResendTimer(seconds) {
        if (dailyLimitReached) {
            lockResend();
            return;
        }

        if (resendTimer) {
            clearInterval(resendTimer);
        }

        resendSeconds = seconds;
        const resendText = document.getElementById("resendCodeText");

        resendText.onclick = null;
        resendText.classList.remove("text-primary", "fw-semibold");
        resendText.classList.add("text-muted");
        resendText.style.cursor = "default";
        resendText.textContent = formatResendLabel(resendSeconds);

        resendTimer = setInterval(function () {
            resendSeconds--;
            resendText.textContent = formatResendLabel(resendSeconds);

            if (resendSeconds <= 0) {
                clearInterval(resendTimer);
                resendTimer = null;
                resendText.textContent = "Resend Code";
                resendText.classList.remove("text-muted");
                resendText.classList.add("text-primary", "fw-semibold");
                resendText.style.cursor = "pointer";
                resendText.onclick = resendPasswordResetCode;
            }
        }, 1000);
    }

    function formatResendLabel(seconds) {
        if (seconds >= 60) {
            const minutes = Math.ceil(seconds / 60);
            return "Resend in " + minutes + "m";
        }
        return "Resend in " + seconds + "s";
    }

    async function resendPasswordResetCode() {
        if (resendSeconds > 0 || dailyLimitReached) {
            return;
        }

        const resendText = document.getElementById("resendCodeText");
        resendText.onclick = null;
        resendText.textContent = "Sending...";
        resendText.style.cursor = "default";

        await sendPasswordResetCode();
    }

    async function updatePassword() {
        if (!validateStep(3)) {
            return;
        }

        const newPassword = document.getElementById("newPassword").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        if (newPassword !== confirmPassword) {
            showToast("Passwords do not match.", "danger");
            return;
        }

        const button = document.getElementById("resetPasswordButton");
        button.disabled = true;
        button.textContent = "Resetting...";

        try {
            const formData = new FormData();
            formData.append("new_password", newPassword);
            formData.append("confirm_password", confirmPassword);

            const response = await fetch("{{ route('forgot-password.update') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = "{{ route('login') }}";
            } else {
                const firstError = data.errors?.new_password?.[0] ?? data.errors?.confirm_password?.[0];
                showToast(firstError ?? data.message ?? "Unable to reset password.", "danger");
            }
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while resetting your password.", "danger");
        } finally {
            button.disabled = false;
            button.textContent = "Reset Password";
        }
    }

    const checkbox = document.getElementById("showNewPassword");
    const passwordInput = document.getElementById("newPassword");

    checkbox.addEventListener("change", function () {
        const type = this.checked ? "text" : "password";
        passwordInput.type = type;
    });

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            showToast(@json($error), "danger");
        @endforeach
    @endif

</script>
@endsection