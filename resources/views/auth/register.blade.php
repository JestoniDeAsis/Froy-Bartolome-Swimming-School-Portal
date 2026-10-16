@extends('layouts.app')

@section('title', 'FBSS Portal - Register')

@section('styles')
<style>
    html, body { 
        height: 100%; 
    }
    .signup-page { 
        min-height: 100vh; 
    }
    .signup-right { 
        background-color: #fff; 
    }
    .signup-card { 
        border: none; 
        box-shadow: none; 
    }
    .signup-title { 
        font-size: 2rem; 
        font-weight: 600; 
    }
    .signup-form-wrap { 
        width: 100%; 
        max-width: 480px; 
        margin-left: auto; 
        margin-right: auto; 
    }
    .resend-text { 
        cursor: pointer; 
        white-space: nowrap; 
    }
    @media (max-width: 1199.98px) { 
        .signup-right 
        { 
            min-height: 100vh; 
        } 
    }
</style>
@endsection

@section('content')
<div class="signup-page row g-0">

    @include('auth.partials.left-panel')

    <div class="signup-right col-12 col-xl-6 d-flex align-items-center justify-content-center">
        <div class="signup-form-wrap px-4 py-4">
            <div class="card signup-card p-4">

                <!-- PROGRESS -->
                <div class="text-center text-muted mb-3">
                    <span id="stepText">Step 1 of 6</span>
                </div>

                <form method="POST" action="{{ route('register.store') }}" id="signupForm">
                    @csrf

                    <!-- ================= STEP 1 ================= -->
                    <div class="signup-step" id="step1">

                        <h2 class="signup-title text-center mb-1">Create Account</h2>
                        <p class="text-center text-muted mb-4">Enter your account details</p>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="Choose a username" minlength="3" maxlength="30" required>
                            <div class="form-text">3–30 characters. Letters, numbers, and underscores only.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailAddress" name="email_address"
                                   placeholder="Enter your email address" required>
                        </div>

                        <button type="button" class="btn btn-primary w-100" id="sendCodeButton" onclick="sendVerificationCode()">
                            Send
                        </button>

                        <hr>

                        <p class="text-center mb-0">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Login</a>
                        </p>

                    </div>

                    <!-- ================= STEP 2 ================= -->
                    <div class="signup-step d-none" id="step2">

                        <h2 class="signup-title text-center mb-1">Verify Email</h2>
                        <p class="text-center text-muted mb-4">Enter the verification code sent to your email.</p>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="usernameStep2" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="emailStep2" readonly>
                                <span class="input-group-text text-muted resend-text" id="resendCodeText">
                                    Resend in 60s
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Verification Code</label>
                            <input type="text" class="form-control" id="verificationCode"
                                   placeholder="Enter 6-digit verification code" maxlength="6"
                                   inputmode="numeric" pattern="[0-9]{6}" required>
                        </div>

                        <button type="button" class="btn btn-primary w-100" id="verifyCodeButton" onclick="verifyRegistrationCode()">
                            Confirm
                        </button>

                    </div>

                    <!-- ================= STEP 3 ================= -->
                    <div class="signup-step d-none" id="step3">

                        <h2 class="signup-title text-center mb-1">Secure Your Account</h2>
                        <p class="text-center text-muted mb-4">Create your password</p>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="usernameStep3" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailStep3" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Create a password" autocomplete="new-password" required>
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
                            <input class="form-check-input" type="checkbox" id="showPassword">
                            <label class="form-check-label" for="showPassword">Show Password</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                                   placeholder="Confirm your password" autocomplete="new-password" required>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="goToStep(4)">Next</button>

                    </div>

                    <!-- ================= STEP 4 ================= -->
                    <div class="signup-step d-none" id="step4">

                        <h2 class="signup-title text-center mb-1">Personal Information</h2>
                        <p class="text-center text-muted mb-4">Tell us about yourself</p>

                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name"
                                   placeholder="Enter your first name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" placeholder="Enter your middle name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name"
                                   placeholder="Enter your last name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Name Suffix</label>
                            <select class="form-select" name="name_suffix">
                                <option value="" selected>None</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="phoneNumber" name="phone_number"
                                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11"
                                   inputmode="numeric" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Sex</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sex" id="male" value="Male" required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sex" id="female" value="Female">
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="goToStep(5)">Next</button>

                    </div>

                    <!-- ================= STEP 5 ================= -->
                    <div class="signup-step d-none" id="step5">

                        <h2 class="signup-title text-center mb-1">Parent / Guardian</h2>
                        <p class="text-center text-muted mb-4">Provide parent or guardian information</p>

                        <div class="mb-3">
                            <label class="form-label">Parent/Guardian Full Name</label>
                            <input type="text" class="form-control" name="parent_guardian_fullname"
                                   placeholder="Enter full name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Relationship to the Registrant</label>
                            <select class="form-select" name="parent_guardian_relationship" required>
                                <option value="" selected disabled>Select relationship</option>
                                <option value="Mother">Mother</option>
                                <option value="Father">Father</option>
                                <option value="Wife">Wife</option>
                                <option value="Husband">Husband</option>
                                <option value="Partner">Partner</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Grandmother">Grandmother</option>
                                <option value="Grandfather">Grandfather</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Legal Guardian">Legal Guardian</option>
                                <option value="Friend">Friend</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parent/Guardian Mobile Number</label>
                            <input type="tel" class="form-control" name="parent_guardian_phone_number"
                                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11"
                                   inputmode="numeric" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(4)">Back</button>
                            <button type="button" class="btn btn-primary w-50" onclick="goToStep(6)">Next</button>
                        </div>

                    </div>

                    <!-- ================= STEP 6 ================= -->
                    <div class="signup-step d-none" id="step6">

                        <h2 class="signup-title text-center mb-1">Waiver and Agreement</h2>
                        <p class="text-center text-muted mb-4">Please review and accept the waiver</p>

                        <div class="border rounded p-3 mb-3" style="max-height: 250px; overflow-y: auto; font-size: 0.875rem;">
                            <h6 class="fw-bold mb-2">Agreement Terms:</h6>
                            <ol class="ps-3 mb-3">
                                <li class="mb-2">
                                    <strong>Program Commitment:</strong><br>
                                    By registering for this program, the participant commits to attend the scheduled classes during the program session.
                                </li>
                                <li class="mb-2">
                                    <strong>Progress Disclaimer:</strong><br>
                                    Each student develops at their own pace. Some may learn skills quickly, while others may need more time and practice.
                                    <ul class="mt-1 mb-0 ps-3">
                                        <li>Our program is designed to support both fast and slow learners with individualized attention and instruction.</li>
                                        <li>There is no guarantee that a participant will achieve a specific level of swimming skill within eight sessions.</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>Makeup Class Policy:</strong><br>
                                    Only ONE makeup class will be offered per student per batch class in the event of an absence.
                                    <ul class="mt-1 mb-0 ps-3">
                                        <li>The makeup class will be scheduled after the last day of the student’s regular program session.</li>
                                        <li>Makeup classes are subject to availability.</li>
                                        <li>Absences must be communicated in advance whenever possible.</li>
                                        <li>No refunds or credits will be provided for missed classes beyond the one makeup session.</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>Fees, Transfers, and Schedule Change Policy:</strong><br>
                                    <ul class="mt-1 mb-0 ps-3">
                                        <li>Program fees are non-refundable under any circumstances.</li>
                                        <li>If a student attends some classes and chooses not to continue, the remaining unused classes may be transferred to another individual.</li>
                                        <li>Transfers must be approved by program staff and are subject to availability and scheduling.</li>
                                        <li>If a participant reserves a slot for a specific schedule and later requests to change that schedule, the original reservation fee is non-refundable.</li>
                                        <li>A new reservation fee must be paid for any schedule change.</li>
                                        <li>Schedule changes must be requested at least 2 weeks before the program start date and are subject to availability.</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>Safety and Conduct:</strong><br>
                                    <ul class="mt-1 mb-0 ps-3">
                                        <li>All participants must follow the safety guidelines and instructions provided by instructors.</li>
                                        <li>Disruptive or unsafe behavior may result in removal from the program without refund.</li>
                                        <li>Parents/guardians are responsible for supervising their children at all times before the class begins and immediately after it ends.</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>Health Disclosure:</strong><br>
                                    Parents/guardians must disclose any medical conditions, allergies, or special needs that may affect the participant’s ability to safely join swimming activities.
                                    <ul class="mt-1 mb-0 ps-3">
                                        <li>If the participant has any existing health concerns, a doctor’s clearance must be submitted before joining the program.</li>
                                    </ul>
                                </li>
                                <li class="mb-3">
                                    <strong>Code of Conduct for Parents/Guardians (Especially for Young Swimmers):</strong><br>
                                    Parents/guardians must refrain from interrupting classes unless in case of emergency. Instructions and corrections are the responsibility of the coaching staff. Parents are encouraged to watch quietly from the designated area.
                                </li>
                            </ol>

                            <h6 class="fw-bold mb-2">PHOTO AND VIDEO RELEASE</h6>
                            <p class="mb-3">
                                I hereby grant FBSS Swimming School, its owners, coaches, staff, and partners the right to take photographs and video recordings of the swimmer during training sessions, classes, events, and related activities[cite: 1].
                            </p>

                            <h6 class="fw-bold mb-2">Waiver and Release of Liability</h6>
                            <p class="mb-2">
                                I acknowledge that participation in swimming activities involves inherent risks, including the possibility of injury. I voluntarily assume all risks associated with participation in the Learn to Swim Program[cite: 1].
                            </p>
                            <p class="mb-2">
                                I, the undersigned, hereby release and hold harmless FBSS Swim School, including its coaches, instructors, staff, affiliated personnel, and partner facilities, from any and all liability, claims, demands, actions, or causes of action arising out of or related to any injury, accident, loss, or damage that may occur during participation in the program[cite: 1].
                            </p>
                            <p class="mb-0 fw-semibold">
                                I confirm that I have read, understood, and agree to all the terms stated above[cite: 1].
                            </p>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="waiverAgreement"
                                   name="waiver_agreement" value="1" required>
                            <label class="form-check-label" for="waiverAgreement">
                                I have read and agree to the waiver and agreement terms[cite: 1].
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(5)">Back</button>
                            <button type="submit" class="btn btn-primary w-50">Register</button>
                        </div>

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

        if (step === 1) {
            const username = document.getElementById("username").value.trim();
            if (!/^[A-Za-z0-9_]{3,30}$/.test(username)) {
                showToast("Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.", "danger");
                return false;
            }
        }

        if (step === 3) {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

            if (!passwordPattern.test(password)) {
                showToast("Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.", "danger");
                document.getElementById("password").focus();
                return false;
            }

            if (password !== confirmPassword) {
                showToast("Passwords do not match.", "danger");
                document.getElementById("confirmPassword").focus();
                return false;
            }
        }

        if (step === 4) {
            const birthdate = document.getElementById("birthdate").value;
            if (!birthdate) {
                showToast("Please enter your date of birth.", "danger");
                return false;
            }

            const [year, month, day] = birthdate.split("-").map(Number);
            const selectedDate = new Date(year, month - 1, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate > today) {
                showToast("Birthdate cannot be in the future.", "danger");
                return false;
            }

            const minimumBirthdate = new Date(today.getFullYear() - 5, today.getMonth(), today.getDate());
            if (selectedDate > minimumBirthdate) {
                showToast("The applicant must be at least 5 years old to be registered.", "danger");
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

        const username = document.getElementById("username").value.trim();
        const emailAddress = document.getElementById("emailAddress").value.trim();

        if (step === 2) {
            document.getElementById("usernameStep2").value = username;
            document.getElementById("emailStep2").value = emailAddress;
        }

        if (step === 3) {
            document.getElementById("usernameStep3").value = username;
            document.getElementById("emailStep3").value = emailAddress;
        }

        document.getElementById("step" + currentStep).classList.add("d-none");
        document.getElementById("step" + step).classList.remove("d-none");

        currentStep = step;
        document.getElementById("stepText").textContent = "Step " + step + " of 6";
    }

    async function sendVerificationCode() {
        if (!validateStep(1)) {
            return;
        }

        const username = document.getElementById("username").value.trim();
        const emailAddress = document.getElementById("emailAddress").value.trim();
        const button = document.getElementById("sendCodeButton");

        button.disabled = true;
        button.textContent = "Sending...";

        try {
            const formData = new FormData();
            formData.append("username", username);
            formData.append("email_address", emailAddress);

            const response = await fetch("{{ route('register.send-code') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json"
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById("usernameStep2").value = username;
                document.getElementById("emailStep2").value = emailAddress;

                if (currentStep !== 2) {
                    goToStep(2);
                }

                document.getElementById("verificationCode").value = "";
                startResendTimer(data.next_wait_seconds ?? 60);
                document.getElementById("verificationCode").focus();
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
            button.textContent = "Send";
        }
    }

    async function verifyRegistrationCode() {
        const verificationCode = document.getElementById("verificationCode").value.trim();

        if (!/^\d{6}$/.test(verificationCode)) {
            showToast("Please enter a valid 6-digit verification code.", "danger");
            return;
        }

        const button = document.getElementById("verifyCodeButton");
        button.disabled = true;
        button.textContent = "Verifying...";

        try {
            const formData = new FormData();
            formData.append("verification_code", verificationCode);

            const response = await fetch("{{ route('register.verify-code') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const username = document.getElementById("username").value.trim();
                const emailAddress = document.getElementById("emailAddress").value.trim();

                document.getElementById("usernameStep3").value = username;
                document.getElementById("emailStep3").value = emailAddress;

                goToStep(3);
                document.getElementById("password").focus();
            } else {
                showToast(data.message ?? "Invalid verification code.", "danger");
            }
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while verifying the code.", "danger");
        } finally {
            button.disabled = false;
            button.textContent = "Confirm";
        }
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
                resendText.onclick = resendRegistrationCode;
            }
        }, 1000);
    }

    async function resendRegistrationCode() {
        if (resendSeconds > 0 || dailyLimitReached) {
            return;
        }

        const resendText = document.getElementById("resendCodeText");
        resendText.onclick = null;
        resendText.textContent = "Sending...";
        resendText.style.cursor = "default";

        await sendVerificationCode();
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

    function formatResendLabel(seconds) {
        if (seconds >= 60) {
            const minutes = Math.ceil(seconds / 60);
            return "Resend in " + minutes + "m";
        }
        return "Resend in " + seconds + "s";
    }

    const checkbox = document.getElementById("showPassword");
    const passwordInput = document.getElementById("password");

    checkbox.addEventListener("change", function () {
        const type = this.checked ? "text" : "password";
        passwordInput.type = type;
    });

    document.getElementById("signupForm").addEventListener("submit", function (event) {
        if (currentStep !== 6) {
            event.preventDefault();
            return;
        }

        if (!validateStep(6)) {
            event.preventDefault();
            return;
        }

        const submitButton = event.submitter;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Registering...";
        }
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            location.reload();
        }
    });

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            showToast(@json($error), "danger");
        @endforeach
    @endif

</script>
@endsection