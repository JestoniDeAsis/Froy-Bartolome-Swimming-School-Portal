@extends('layouts.app')

@section('title', 'FBSS Portal - Forgot Username')

@section('styles')
<style>
    html, body {
        height: 100%;
    }
    .forgot-username-page {
        min-height: 100vh;
    }
    .forgot-username-right {
        background-color: #fff;
    }
    .forgot-username-form-wrap {
        width: 100%;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }
    .forgot-username-card {
        border: none;
        box-shadow: none;
    }
    .forgot-username-title {
        font-size: 2rem;
        font-weight: 600;
    }
    @media (max-width: 1199.98px) {
        .forgot-username-right {
            min-height: 100vh;
        }
    }
</style>
@endsection

@section('content')
<div class="forgot-username-page row g-0">

    @include('auth.partials.left-panel')

    <div class="forgot-username-right col-12 col-xl-6 d-flex align-items-center justify-content-center">
        <div class="forgot-username-form-wrap px-4 py-4">
            <div class="card forgot-username-card p-4">

                <!-- ================= REQUEST ================= -->
                <div id="requestState">

                    <h2 class="forgot-username-title text-center mb-1">Forgot Username?</h2>
                    <p class="text-center text-muted mb-4">
                        Enter the email address you used to register and we will send your username to it.
                    </p>

                    <form id="forgotUsernameForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="emailAddress">Email Address</label>
                            <input type="email"
                                   class="form-control"
                                   id="emailAddress"
                                   name="email_address"
                                   placeholder="Enter your email address"
                                   maxlength="100"
                                   autocomplete="email"
                                   required>
                            <div class="form-text">For your security, we only send the username to the email address on the account.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="sendUsernameButton">
                            Send Username
                        </button>
                    </form>

                    <hr>

                    <p class="text-center mb-1">
                        Remembered it?
                        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Log in here</a>
                    </p>
                    <p class="text-center mb-0">
                        Forgot your password instead?
                        <a href="{{ url('forgot-password') }}" class="text-decoration-none fw-semibold">Reset it here</a>
                    </p>
                </div>

                <!-- ================= SENT ================= -->
                <div id="sentState" class="d-none">

                    <h2 class="forgot-username-title text-center mb-1">Check Your Email</h2>
                    <p class="text-center text-muted mb-4">
                        We sent your username to <strong id="sentEmail"></strong>.
                        It can take a minute to arrive, so please check your spam folder too.
                    </p>

                    <a href="{{ route('login') }}" class="btn btn-primary w-100 mb-3">Back to Login</a>

                    <p class="text-center text-muted mb-0">
                        Did not get it?
                        <span id="resendText" class="text-muted">Resend in 30s</span>
                    </p>
                    <p class="text-center small text-muted mt-1 mb-0" id="sendsLeft"></p>

                    <hr>

                    <p class="text-center mb-0">
                        Wrong email address?
                        <a href="#" id="useAnotherEmail" class="text-decoration-none fw-semibold">Use a different one</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const EMAIL_PATTERN = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/;

    const emailInput = document.getElementById("emailAddress");
    const sendButton = document.getElementById("sendUsernameButton");
    const resendText = document.getElementById("resendText");

    let resendTimer = null;
    let resendSeconds = 0;
    let limitReached = false;
    let sending = false;

    // ---------- remember the cooldown across refreshes (the server still enforces the real limits) ----------
    const STORAGE_KEY = "fbssForgotUsername";

    function saveState(email, seconds, limited, remaining, max) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                email: email,
                until: Date.now() + seconds * 1000,
                limited: !!limited,
                remaining: remaining,
                max: max
            }));
        } catch (error) { /* storage unavailable, nothing to do */ }
    }

    function loadState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY));
        } catch (error) {
            return null;
        }
    }

    function clearState() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (error) { /* ignore */ }
    }

    // ---------- email input: no spaces ----------
    emailInput.addEventListener("input", function () {
        this.value = this.value.replace(/\s/g, "");
    });

    function showSendsLeft(remaining, max) {
        const line = document.getElementById("sendsLeft");

        if (remaining === undefined || remaining === null) {
            line.textContent = "";
            return;
        }

        line.textContent = remaining === 0
            ? "You have used all " + (max ?? 3) + " sends for today."
            : remaining + " of " + (max ?? 3) + " sends left today.";
    }

    // ---------- show / hide states ----------
    function showSent(email) {
        document.getElementById("sentEmail").textContent = email;
        document.getElementById("requestState").classList.add("d-none");
        document.getElementById("sentState").classList.remove("d-none");
    }

    function showRequest() {
        document.getElementById("sentState").classList.add("d-none");
        document.getElementById("requestState").classList.remove("d-none");
        emailInput.focus();
    }

    document.getElementById("useAnotherEmail").addEventListener("click", function (event) {
        event.preventDefault();
        clearState();
        limitReached = false;
        showRequest();
    });

    // ---------- resend timer ----------
    function formatWait(seconds) {
        if (seconds >= 60) {
            return Math.ceil(seconds / 60) + "m";
        }
        return seconds + "s";
    }

    function setResendLink(enabled) {
        resendText.classList.toggle("text-muted", !enabled);
        resendText.classList.toggle("text-primary", enabled);
        resendText.classList.toggle("fw-semibold", enabled);
        resendText.style.cursor = enabled ? "pointer" : "default";
        resendText.onclick = enabled ? resendUsername : null;
    }

    function lockResend(seconds) {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
        setResendLink(false);
        resendText.textContent = seconds
            ? "Limit reached. Try again in " + formatWait(seconds)
            : "Limit reached. Try again later";
    }

    function startResendTimer(seconds) {
        if (limitReached) {
            lockResend();
            return;
        }

        if (resendTimer) {
            clearInterval(resendTimer);
        }

        resendSeconds = seconds;
        setResendLink(false);
        resendText.textContent = "Resend in " + formatWait(resendSeconds);

        resendTimer = setInterval(function () {
            resendSeconds--;

            if (resendSeconds > 0) {
                resendText.textContent = "Resend in " + formatWait(resendSeconds);
                return;
            }

            clearInterval(resendTimer);
            resendTimer = null;
            resendText.textContent = "Resend Username";
            setResendLink(true);
        }, 1000);
    }

    // ---------- sending ----------
    async function sendUsername(email) {
        if (sending) {
            return false;
        }

        sending = true;

        try {
            const formData = new FormData();
            formData.append("email_address", email);

            const response = await fetch("{{ route('forgot-username.send') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const wait = data.next_wait_seconds ?? 30;

                limitReached = !!data.limit_reached;
                saveState(email, wait, limitReached, data.remaining, data.daily_max);
                showSent(email);
                showSendsLeft(data.remaining, data.daily_max);

                if (limitReached) {
                    lockResend(wait);
                } else {
                    startResendTimer(wait);
                }

                return true;
            }

            if (data.reason === "cooldown") {
                // This email was just sent one. Show the screen with the time that is left.
                const wait = data.next_wait_seconds ?? 30;

                saveState(email, wait, false, data.remaining, data.daily_max);
                showSent(email);
                showSendsLeft(data.remaining, data.daily_max);
                startResendTimer(wait);
            }

            if (data.reason === "limit") {
                const wait = data.next_wait_seconds ?? 3600;

                if (!document.getElementById("sentState").classList.contains("d-none")) {
                    limitReached = true;
                    saveState(email, wait, true, 0, data.daily_max);
                    showSendsLeft(0, data.daily_max);
                    lockResend(wait);
                }
            }

            const firstError = data.errors?.email_address?.[0];
            showToast(firstError ?? data.message ?? "Unable to send the username right now.", "danger");
        } catch (error) {
            console.error(error);
            showToast("Something went wrong. Please try again.", "danger");
        } finally {
            sending = false;
        }

        return false;
    }

    document.getElementById("forgotUsernameForm").addEventListener("submit", async function (event) {
        event.preventDefault();

        emailInput.value = emailInput.value.trim().toLowerCase();

        if (!EMAIL_PATTERN.test(emailInput.value)) {
            showToast("Please enter a valid email address, like name@example.com.", "danger");
            emailInput.focus();
            return;
        }

        sendButton.disabled = true;
        sendButton.textContent = "Sending...";

        await sendUsername(emailInput.value);

        sendButton.disabled = false;
        sendButton.textContent = "Send Username";
    });

    async function resendUsername() {
        if (resendSeconds > 0 || limitReached || sending) {
            return;
        }

        setResendLink(false);
        resendText.textContent = "Sending...";

        const ok = await sendUsername(emailInput.value);

        if (!ok && !limitReached) {
            resendText.textContent = "Resend Username";
            setResendLink(true);
        }
    }

    // ---------- restore the "Check Your Email" screen after a refresh ----------
    (function restoreState() {
        const saved = loadState();

        if (!saved || !saved.email) {
            return;
        }

        const remaining = Math.ceil((saved.until - Date.now()) / 1000);

        if (remaining <= 0) {
            clearState();
            return;
        }

        emailInput.value = saved.email;
        showSent(saved.email);
        showSendsLeft(saved.remaining, saved.max);

        if (saved.limited) {
            limitReached = true;
            lockResend(remaining);
        } else {
            startResendTimer(remaining);
        }
    })();
</script>
@endsection