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
    .toggle-password {
        display: flex;
        align-items: center;
    }
    .rule-icon {
        display: inline-block;
        width: 1.2em;
        text-align: center;
    }
    .resend-text {
        cursor: pointer;
        white-space: nowrap;
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
                        <p class="text-center text-muted mb-4">Enter the email address you used to register and we will send you a verification code.</p>

                        <div class="mb-3">
                            <label class="form-label" for="emailAddress">Email Address</label>

                            <input type="email"
                                class="form-control"
                                id="emailAddress"
                                name="email_address"
                                placeholder="Enter your email address"
                                maxlength="100"
                                autocomplete="email"
                                required
                            >
                            <div class="form-text">We will email a verification code to confirm it is you.</div>
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
                        <p class="text-center text-muted mb-4">We sent a 6-digit verification code to your email to confirm that you own this account. Enter it below to continue.</p>

                        <div class="mb-3">
                            <label class="form-label" for="emailStep2">Email Address</label>
                            
                            <div class="input-group">

                                <input type="email" class="form-control" id="emailStep2" readonly>

                                <span class="input-group-text text-muted resend-text" id="resendCodeText">
                                    Resend in 60s
                                </span>

                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="passwordResetCode">Verification Code</label>

                            <input
                                type="text"
                                class="form-control"
                                id="passwordResetCode"
                                name="verification_code"
                                autocomplete="one-time-code"
                                placeholder="Enter 6-digit verification code"
                                maxlength="6"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                required
                            >
                            <div class="form-text">The code expires in 10 minutes. Did not get it? Check your spam folder, or use Resend Code above once the timer ends.</div>
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
                            <label class="form-label" for="newPassword">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" name="new_password"
                                       placeholder="Enter your new password" maxlength="64" autocomplete="new-password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="newPassword" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                            </div>
                            <ul class="list-unstyled small mt-2 mb-0" id="passwordRules">
                                <li data-rule="length" class="text-muted"><span class="rule-icon">○</span> At least 8 characters</li>
                                <li data-rule="upper" class="text-muted"><span class="rule-icon">○</span> At least one uppercase letter</li>
                                <li data-rule="lower" class="text-muted"><span class="rule-icon">○</span> At least one lowercase letter</li>
                                <li data-rule="number" class="text-muted"><span class="rule-icon">○</span> At least one number</li>
                                <li data-rule="special" class="text-muted"><span class="rule-icon">○</span> At least one special character</li>
                                <li data-rule="nospace" class="text-muted"><span class="rule-icon">○</span> No spaces</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="confirmPassword">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                                       placeholder="Confirm your new password" maxlength="64" autocomplete="new-password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="confirmPassword" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                            </div>
                            <div class="form-text">Type it again. Copy and paste is turned off for password fields.</div>
                            <div class="form-text" id="passwordMatchText"></div>
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

    const EMAIL_PATTERN = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/;
    const PASSWORD_PATTERN = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

    function validateStep(step) {
        const stepElement = document.getElementById("step" + step);
        const inputs = stepElement.querySelectorAll("input, select, textarea");

        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                return false;
            }
        }

        if (step === 1) {
            const emailInput = document.getElementById("emailAddress");
            emailInput.value = emailInput.value.trim().toLowerCase();

            if (!EMAIL_PATTERN.test(emailInput.value)) {
                showToast("Please enter a valid email address, like name@example.com.", "danger");
                emailInput.focus();
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

        if (!PASSWORD_PATTERN.test(newPassword)) {
            showToast("Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.", "danger");
            document.getElementById("newPassword").focus();
            return;
        }

        if (/\s/.test(newPassword)) {
            showToast("Password cannot contain spaces.", "danger");
            document.getElementById("newPassword").focus();
            return;
        }

        if (newPassword !== confirmPassword) {
            showToast("Passwords do not match.", "danger");
            document.getElementById("confirmPassword").focus();
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

    // ---------- live password checklist ----------
    const passwordRules = {
        length: function (v) { return v.length >= 8; },
        upper: function (v) { return /[A-Z]/.test(v); },
        lower: function (v) { return /[a-z]/.test(v); },
        number: function (v) { return /\d/.test(v); },
        special: function (v) { return /[^A-Za-z0-9]/.test(v); },
        nospace: function (v) { return v !== "" && !/\s/.test(v); }
    };

    function updatePasswordMatch() {
        const password = document.getElementById("newPassword").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const matchText = document.getElementById("passwordMatchText");

        if (confirmPassword === "") {
            matchText.textContent = "";
            matchText.className = "form-text";
            return;
        }

        const same = password === confirmPassword;
        matchText.textContent = same ? "Passwords match." : "Passwords do not match.";
        matchText.className = "form-text " + (same ? "text-success" : "text-danger");
    }

    function updatePasswordChecklist() {
        const value = document.getElementById("newPassword").value;

        document.querySelectorAll("#passwordRules li").forEach(function (li) {
            const ok = passwordRules[li.dataset.rule](value);
            li.classList.toggle("text-success", ok);
            li.classList.toggle("text-muted", !ok);
            li.querySelector(".rule-icon").textContent = ok ? "✓" : "○";
        });

        updatePasswordMatch();
    }

    document.getElementById("newPassword").addEventListener("input", updatePasswordChecklist);
    document.getElementById("confirmPassword").addEventListener("input", updatePasswordMatch);

    // ---------- no copy / paste on password fields ----------
    let lastClipboardToast = 0;

    ["newPassword", "confirmPassword"].forEach(function (id) {
        const field = document.getElementById(id);

        ["paste", "copy", "cut", "drop", "dragstart", "contextmenu"].forEach(function (eventName) {
            field.addEventListener(eventName, function (event) {
                event.preventDefault();

                if (eventName === "contextmenu") {
                    return;
                }

                const now = Date.now();
                if (now - lastClipboardToast > 2000) {
                    lastClipboardToast = now;
                    showToast("Copy and paste are disabled on password fields. Please type your password.", "danger");
                }
            });
        });
    });

    // ---------- input filters ----------
    document.getElementById("passwordResetCode").addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "");
    });

    document.getElementById("emailAddress").addEventListener("input", function () {
        this.value = this.value.replace(/\s/g, "");
    });

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            showToast(@json($error), "danger");
        @endforeach
    @endif

</script>
@endsection