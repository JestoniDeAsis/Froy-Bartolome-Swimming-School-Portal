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
    .toggle-password {
        display: flex;
        align-items: center;
    }
    .rule-icon {
        display: inline-block;
        width: 1.2em;
        text-align: center;
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
        font-weight: 600;
    }
    #waiverDialog {
        border: none;
        border-radius: 0.75rem;
        padding: 0;
        width: min(720px, 94vw);
        max-height: 90vh;
        background: #fff;
        color: #212529;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }
    #waiverDialog[open] {
        display: flex;
        flex-direction: column;
    }
    #waiverDialog::backdrop {
        background: rgba(0, 0, 0, 0.55);
    }
    .waiver-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    .waiver-body {
        padding: 1.25rem 1.5rem;
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
        font-size: 1rem;
        line-height: 1.6;
    }
    .waiver-footer {
        padding: 0.85rem 1.5rem;
        border-top: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
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
                        <p class="text-center text-muted mb-4">Who is this registration for? <span class="text-danger">*</span></p>

                        <div class="form-check border rounded p-3 ps-5 mb-2">
                            <input class="form-check-input" type="radio" name="account_handler"
                                   id="handlerParent" value="parent_guardian" required>
                            <label class="form-check-label w-100" for="handlerParent">
                                <strong>I'm a parent or guardian</strong><br>
                                <span class="text-muted small">I'm registering someone else, like my child. I will manage the account, payments, and classes.</span>
                            </label>
                        </div>

                        <div class="form-check border rounded p-3 ps-5 mb-4">
                            <input class="form-check-input" type="radio" name="account_handler"
                                   id="handlerSelf" value="self" required>
                            <label class="form-check-label w-100" for="handlerSelf">
                                <strong>I'm registering for myself</strong><br>
                                <span class="text-muted small">I'm 18 or older and will manage my own account, payments, and classes.</span>
                            </label>
                        </div>

                        <button type="button" class="btn btn-primary w-100" onclick="goToStep(2)">Next</button>

                        <hr>

                        <p class="text-center mb-0">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Login</a>
                        </p>

                    </div>

                    <!-- ================= STEP 2 ================= -->
                    <div class="signup-step d-none" id="step2">

                        <h2 class="signup-title text-center mb-1">Account Details</h2>
                        <p class="text-center text-muted mb-3">Set up the login for this account</p>
                        <p class="small text-muted text-center mb-3">Fields marked with a red <span class="text-danger fw-bold">*</span> are required.</p>

                        <div class="mb-3">
                            <label class="form-label required-field" for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="Choose a username" minlength="3" maxlength="30" autocomplete="username" data-counter data-username required>
                            <div class="form-text">3–30 characters. Letters, numbers, and underscores only.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="emailAddress">Email Address</label>
                            <input type="email" class="form-control" id="emailAddress" name="email_address"
                                   placeholder="Enter your email address" maxlength="100" autocomplete="email" data-counter data-email required>
                            <div class="form-text">We will send a verification code to this email at the last step.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="password">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Create a password" maxlength="64" autocomplete="new-password" data-counter required>
                                <button type="button" class="btn btn-outline-secondary toggle-password"
                                        data-target="password" aria-label="Show password" title="Show password">
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
                                <li data-rule="nouser" class="text-muted"><span class="rule-icon">○</span> Does not contain your username</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="confirmPassword">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                                       placeholder="Confirm your password" maxlength="64" autocomplete="new-password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password"
                                        data-target="confirmPassword" aria-label="Show password" title="Show password">
                                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                            <div class="form-text">Type it again. Copy and paste is turned off for password fields.</div>
                            <div class="form-text" id="passwordMatchText"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(1)">Back</button>
                            <button type="button" class="btn btn-primary w-50" onclick="goToStep(3)">Next</button>
                        </div>

                    </div>

                    <!-- ================= STEP 3 ================= -->
                    <div class="signup-step d-none" id="step3">

                        <h2 class="signup-title text-center mb-1"
                            data-parent="Applicant Information" data-self="Personal Information">Personal Information</h2>
                        <p class="text-center text-muted mb-3"
                           data-parent="Tell us about the person who will take the classes"
                           data-self="Tell us about yourself">Tell us about yourself</p>
                        <p class="small text-muted text-center mb-3">Fields marked with a red <span class="text-danger fw-bold">*</span> are required.</p>

                        <div class="mb-3">
                            <label class="form-label required-field" for="firstName" data-applicant="First Name">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name"
                                   data-applicant-ph="first name" placeholder="Enter your first name" maxlength="50" data-counter data-name required>
                            <div class="form-text">At least 2 characters. Letters only, no numbers or symbols.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="middleName" data-applicant="Middle Name">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middle_name"
                                   data-applicant-ph="middle name" placeholder="Enter your middle name" maxlength="50" data-counter data-name>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="lastName" data-applicant="Last Name">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name"
                                   data-applicant-ph="last name" placeholder="Enter your last name" maxlength="50" data-counter data-name required>
                            <div class="form-text">At least 2 characters. Letters only, no numbers or symbols.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="nameSuffix" data-applicant="Name Suffix">Name Suffix</label>
                            <select class="form-select" id="nameSuffix" name="name_suffix">
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
                            <label class="form-label required-field" for="birthdate" data-applicant="Date of Birth">Date of Birth</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                            <div class="form-text" data-parent="The applicant must be at least 5 years old." data-self="You must be at least 18 years old.">You must be at least 18 years old.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="phoneNumber" data-applicant="Mobile Number">Mobile Number</label>
                            <input type="tel" class="form-control" id="phoneNumber" name="phone_number" data-counter data-digits
                                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11"
                                   inputmode="numeric" required>
                            <div class="form-text">11 digits, starting with 09 (for example, 09123456789).</div>
                            <div class="form-text" data-parent="No mobile number? You can use your own." data-self=""></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field d-block" data-applicant="Sex">Sex</label>
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

                        <div class="mb-3">
                            <label class="form-label required-field" for="address" data-applicant="Home Address">Home Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2" minlength="10" maxlength="255" data-counter data-address autocomplete="street-address"
                                      placeholder="House no., street, barangay, city, province" required></textarea>
                            <div class="form-text">At least 10 characters. Include your house no., street, barangay, city, and province.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(2)">Back</button>
                            <button type="button" class="btn btn-primary w-50" onclick="goToStep(4)">Next</button>
                        </div>

                    </div>

                    <!-- ================= STEP 4 ================= -->
                    <div class="signup-step d-none" id="step4">

                        <h2 class="signup-title text-center mb-1"
                            data-parent="Parent / Guardian" data-self="Emergency Contact">Parent / Guardian</h2>
                        <p class="text-center text-muted mb-3"
                           data-parent="Provide your information as the parent or guardian"
                           data-self="Someone we can call in case of an emergency">Provide parent or guardian information</p>
                        <p class="small text-muted text-center mb-3">Fields marked with a red <span class="text-danger fw-bold">*</span> are required.</p>

                        <div class="mb-3">
                            <label class="form-label required-field" for="parentFullName"
                                   data-parent="Your Full Name" data-self="Contact Person's Full Name">Full Name</label>
                            <input type="text" class="form-control" id="parentFullName" name="parent_guardian_fullname"
                                   placeholder="Enter full name" maxlength="100" data-counter data-name required>
                            <div class="form-text">Enter first and last name. Letters only, no numbers or symbols.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="parentRelationship"
                                   data-parent="Your Relationship to the Applicant"
                                   data-self="Their Relationship to You">Relationship</label>
                            <select class="form-select" id="parentRelationship" name="parent_guardian_relationship" required>
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
                            <label class="form-label required-field" for="parentPhone"
                                   data-parent="Your Mobile Number"
                                   data-self="Contact Person's Mobile Number">Mobile Number</label>
                            <input type="tel" class="form-control" id="parentPhone" name="parent_guardian_phone_number" data-counter data-digits
                                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11"
                                   inputmode="numeric" required>
                            <div class="form-text">11 digits, starting with 09 (for example, 09123456789).</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(3)">Back</button>
                            <button type="button" class="btn btn-primary w-50" onclick="goToStep(5)">Next</button>
                        </div>

                    </div>

                    <!-- ================= STEP 5 ================= -->
                    <div class="signup-step d-none" id="step5">

                        <h2 class="signup-title text-center mb-1">Waiver and Agreement</h2>
                        <p class="text-center text-muted mb-4">Please review and accept the waiver</p>

                        <div class="border rounded p-3 mb-3 text-center">
                            <p class="mb-3">You need to read the full Waiver and Agreement before you can register.</p>
                            <button type="button" class="btn btn-outline-primary" id="openWaiverButton">Read Waiver and Agreement</button>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="waiverCheck">
                            <label class="form-check-label required-field" for="waiverCheck">
                                I have read and agree to the waiver and agreement terms.
                            </label>
                        </div>

                        <input type="hidden" name="waiver_agreement" id="waiverAgreement" value="">

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(4)">Back</button>
                            <button type="button" class="btn btn-primary w-50" id="continueButton" onclick="continueToVerification()">Continue</button>
                        </div>

                    </div>

                    <!-- ================= STEP 6 ================= -->
                    <div class="signup-step d-none" id="step6">

                        <h2 class="signup-title text-center mb-1">Verify Email</h2>
                        <p class="text-center text-muted mb-4">Last step. We sent a 6-digit verification code to your email to confirm that this address belongs to you. Enter it below to finish creating your account.</p>

                        <div class="mb-3">
                            <label class="form-label" for="emailStep6">Email Address</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="emailStep6" readonly>
                                <span class="input-group-text text-muted resend-text" id="resendCodeText">
                                    Resend in 60s
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required-field" for="verificationCode">Verification Code</label>
                            <input type="text" class="form-control" id="verificationCode" autocomplete="one-time-code"
                                   placeholder="Enter 6-digit verification code" maxlength="6"
                                   inputmode="numeric" pattern="[0-9]{6}" required>
                            <div class="form-text">The code expires in 10 minutes. Did not get it? Check your spam folder, or use Resend Code above once the timer ends.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50" onclick="goToStep(5)">Back</button>
                            <button type="button" class="btn btn-primary w-50" id="verifyCodeButton" onclick="verifyRegistrationCode()">Confirm &amp; Register</button>
                        </div>

                    </div>

                </form>

            </div>
        </div>
    </div>

</div>
<dialog id="waiverDialog" aria-labelledby="waiverTitle">
    <div class="waiver-header d-flex justify-content-between align-items-start">
        <div>
            <h5 class="mb-0" id="waiverTitle">Waiver and Agreement</h5>
            <small class="text-muted">Froy Bartolome Swimming School</small>
        </div>
        <button type="button" class="btn-close" id="closeWaiverX" aria-label="Close"></button>
    </div>

    <div class="waiver-body" id="waiverBody">
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
                                I hereby grant FBSS Swimming School, its owners, coaches, staff, and partners the right to take photographs and video recordings of the swimmer during training sessions, classes, events, and related activities.
                            </p>

                            <h6 class="fw-bold mb-2">Waiver and Release of Liability</h6>
                            <p class="mb-2">
                                I acknowledge that participation in swimming activities involves inherent risks, including the possibility of injury. I voluntarily assume all risks associated with participation in the Learn to Swim Program.
                            </p>
                            <p class="mb-2">
                                I, the undersigned, hereby release and hold harmless FBSS Swim School, including its coaches, instructors, staff, affiliated personnel, and partner facilities, from any and all liability, claims, demands, actions, or causes of action arising out of or related to any injury, accident, loss, or damage that may occur during participation in the program.
                            </p>
                            <p class="mb-0 fw-semibold">
                                I confirm that I have read, understood, and agree to all the terms stated above.
                            </p>
                        
    </div>

    <div class="waiver-footer">
        <small class="text-muted me-auto" id="waiverHint">Scroll to the bottom to enable the agree button.</small>
        <button type="button" class="btn btn-outline-secondary" id="closeWaiverButton">Close</button>
        <button type="button" class="btn btn-primary" id="agreeWaiverButton" disabled>I Have Read and Agree</button>
    </div>
</dialog>

@endsection

@section('scripts')
<script>

    let currentStep = 1;
    let resendTimer = null;
    let resendSeconds = 60;
    let dailyLimitReached = false;
    let codeSentKey = null;
    let submitting = false;

    const val = (id) => document.getElementById(id).value.trim();

    function getHandler() {
        const checked = document.querySelector('input[name="account_handler"]:checked');
        return checked ? checked.value : null;
    }

    function applyRegistrantType() {
        const isParent = getHandler() === "parent_guardian";
        const key = isParent ? "parent" : "self";

        document.querySelectorAll("[data-parent]").forEach(function (el) {
            el.textContent = el.dataset[key];
        });

        document.querySelectorAll("[data-applicant]").forEach(function (el) {
            el.textContent = (isParent ? "Applicant's " : "") + el.dataset.applicant;
        });

        document.querySelectorAll("[data-applicant-ph]").forEach(function (el) {
            el.placeholder = (isParent ? "Enter the applicant's " : "Enter your ") + el.dataset.applicantPh;
        });
    }

    document.querySelectorAll('input[name="account_handler"]').forEach(function (radio) {
        radio.addEventListener("change", applyRegistrantType);
    });

    const NAME_PATTERN = /^\p{L}[\p{L} .'’-]*$/u;
    const EMAIL_PATTERN = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/;
    const MOBILE_PATTERN = /^09\d{9}$/;

    function validateName(fieldName, label, required) {
        const input = document.querySelector('[name="' + fieldName + '"]');
        const value = input.value.trim();

        if (value === "" && !required) {
            return true;
        }

        if (required && value.length < 2) {
            showToast(label + " must be at least 2 characters.", "danger");
            input.focus();
            return false;
        }

        if (!NAME_PATTERN.test(value)) {
            showToast(label + " can only contain letters, spaces, periods, apostrophes, and hyphens.", "danger");
            input.focus();
            return false;
        }

        return true;
    }

    function validateStep(step) {
        const stepElement = document.getElementById("step" + step);
        const inputs = stepElement.querySelectorAll("input, select, textarea");

        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                return false;
            }
        }

        if (step === 2) {
            if (!/^[A-Za-z0-9_]{3,30}$/.test(val("username"))) {
                showToast("Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.", "danger");
                return false;
            }

            const emailInput = document.getElementById("emailAddress");
            emailInput.value = emailInput.value.trim().toLowerCase();

            if (!EMAIL_PATTERN.test(emailInput.value)) {
                showToast("Please enter a valid email address, like name@example.com.", "danger");
                emailInput.focus();
                return false;
            }

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

            if (!passwordPattern.test(password)) {
                showToast("Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.", "danger");
                document.getElementById("password").focus();
                return false;
            }

            if (/\s/.test(password)) {
                showToast("Password cannot contain spaces.", "danger");
                document.getElementById("password").focus();
                return false;
            }

            if (password.toLowerCase().includes(val("username").toLowerCase())) {
                showToast("Password must not contain your username.", "danger");
                document.getElementById("password").focus();
                return false;
            }

            if (password !== confirmPassword) {
                showToast("Passwords do not match.", "danger");
                document.getElementById("confirmPassword").focus();
                return false;
            }
        }

        if (step === 3) {
            if (!validateName("first_name", "First name", true)) return false;
            if (!validateName("middle_name", "Middle name", false)) return false;
            if (!validateName("last_name", "Last name", true)) return false;

            const birthdate = document.getElementById("birthdate").value;
            if (!birthdate) {
                showToast("Please enter the date of birth.", "danger");
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

            const oldestBirthdate = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());

            if (selectedDate < oldestBirthdate) {
                showToast("Please enter a valid date of birth.", "danger");
                return false;
            }

            const isSelf = getHandler() === "self";
            const minAge = isSelf ? 18 : 5;
            const minimumBirthdate = new Date(today.getFullYear() - minAge, today.getMonth(), today.getDate());

            if (selectedDate > minimumBirthdate) {
                showToast(
                    isSelf
                        ? "You must be at least 18 years old to register for yourself. Ask a parent or guardian to register you."
                        : "The applicant must be at least 5 years old to be registered.",
                    "danger"
                );
                return false;
            }
        }

        if (step === 3) {
            const phoneInput = document.getElementById("phoneNumber");
            if (!MOBILE_PATTERN.test(phoneInput.value.trim())) {
                showToast("Mobile number must be 11 digits and start with 09.", "danger");
                phoneInput.focus();
                return false;
            }

            const addressInput = document.getElementById("address");
            const address = addressInput.value.trim().replace(/\s+/g, " ");
            addressInput.value = address;

            if (address.length < 10 || !/\p{L}/u.test(address)) {
                showToast("Please enter a complete home address (at least 10 characters) with your street, barangay, city, and province.", "danger");
                addressInput.focus();
                return false;
            }
        }

        if (step === 4) {
            if (!validateName("parent_guardian_fullname", "Full name", true)) return false;

            const fullNameInput = document.getElementById("parentFullName");
            if (fullNameInput.value.trim().split(/\s+/).length < 2) {
                showToast("Please enter the full name (first and last name).", "danger");
                fullNameInput.focus();
                return false;
            }

            const parentPhone = document.getElementById("parentPhone");
            if (!MOBILE_PATTERN.test(parentPhone.value.trim())) {
                showToast("Mobile number must be 11 digits and start with 09.", "danger");
                parentPhone.focus();
                return false;
            }
        }

        if (step === 5) {
            if (document.getElementById("waiverAgreement").value !== "1") {
                showToast("Please read the waiver and tick the agreement box to continue.", "danger");
                return false;
            }
        }

        return true;
    }

    function goToStep(step) {
        if (step > currentStep && !validateStep(currentStep)) {
            return;
        }

        if (step === 6) {
            document.getElementById("emailStep6").value = val("emailAddress");
        }

        document.getElementById("step" + currentStep).classList.add("d-none");
        document.getElementById("step" + step).classList.remove("d-none");

        currentStep = step;
        document.getElementById("stepText").textContent = "Step " + step + " of 6";
    }

    async function continueToVerification() {
        if (!validateStep(5)) {
            return;
        }

        // Code already sent for this same username + email, so just go to the verify step
        if (codeSentKey === val("username") + "|" + val("emailAddress")) {
            goToStep(6);
            return;
        }

        await sendVerificationCode();
    }

    async function sendVerificationCode() {
        const username = val("username");
        const emailAddress = val("emailAddress");
        const button = document.getElementById("continueButton");

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
                codeSentKey = username + "|" + emailAddress;
                document.getElementById("emailStep6").value = emailAddress;

                if (currentStep !== 6) {
                    goToStep(6);
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

                // Username or email already taken: send them back to the account step
                if (data.errors && (data.errors.username || data.errors.email_address)) {
                    goToStep(2);
                }
            }
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while sending the verification code.", "danger");
        } finally {
            button.disabled = false;
            button.textContent = "Continue";
        }
    }

    async function verifyRegistrationCode() {
        const verificationCode = val("verificationCode");

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
                submitting = true;
                button.textContent = "Registering...";
                document.getElementById("signupForm").submit();
                return;
            }

            showToast(data.message ?? "Invalid verification code.", "danger");
        } catch (error) {
            console.error(error);
            showToast("Something went wrong while verifying the code.", "danger");
        } finally {
            if (!submitting) {
                button.disabled = false;
                button.textContent = "Confirm & Register";
            }
        }
    }

    // Stops the Enter key from submitting early. The real submit happens after the code is verified.
    document.getElementById("signupForm").addEventListener("submit", function (event) {
        if (!submitting) {
            event.preventDefault();
        }
    });

    // ---------- character counters and input filters ----------
    document.querySelectorAll("[data-counter]").forEach(function (input) {
        const limit = input.getAttribute("maxlength");

        const wrap = document.createElement("div");
        wrap.className = "form-text d-flex justify-content-between";
        wrap.innerHTML = '<span class="char-msg text-danger"></span><span class="char-count ms-auto"></span>';
        (input.closest(".input-group") || input).after(wrap);

        const count = wrap.querySelector(".char-count");
        const msg = wrap.querySelector(".char-msg");

        function updateCount() {
            count.textContent = input.value.length + " / " + limit;
            count.classList.toggle("text-danger", input.value.length >= Number(limit));
        }

        input.addEventListener("input", function () {
            const original = input.value;
            let cleaned = original;
            let message = "";

            if (input.hasAttribute("data-name")) {
                cleaned = cleaned
                    .replace(/[^\p{L} .'’-]/gu, "")
                    .replace(/^[^\p{L}]+/u, "")
                    .replace(/ {2,}/g, " ");
                message = "Only letters, spaces, periods, apostrophes, and hyphens are allowed.";
            }

            if (input.hasAttribute("data-digits")) {
                cleaned = cleaned.replace(/\D/g, "");
                message = "Numbers only.";
            }

            if (input.hasAttribute("data-username")) {
                cleaned = cleaned.replace(/[^A-Za-z0-9_]/g, "");
                message = "Only letters, numbers, and underscores are allowed.";
            }

            if (input.hasAttribute("data-email")) {
                cleaned = cleaned.replace(/\s/g, "");
                message = "Spaces are not allowed in an email address.";
            }

            if (input.hasAttribute("data-address")) {
                cleaned = cleaned
                    .replace(/[^\p{L}\p{N} .,#'’\/-]/gu, "")
                    .replace(/^ +/, "")
                    .replace(/ {2,}/g, " ");
                message = "Only letters, numbers, spaces, and . , # ' / - are allowed.";
            }

            if (cleaned !== original) {
                input.value = cleaned;
                msg.textContent = message;
            } else {
                msg.textContent = "";
            }

            updateCount();
        });

        updateCount();
    });

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

    // ---------- date of birth limits ----------
    (function () {
        const birth = document.getElementById("birthdate");
        const now = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        const monthDay = "-" + pad(now.getMonth() + 1) + "-" + pad(now.getDate());

        birth.max = now.getFullYear() + monthDay;
        birth.min = (now.getFullYear() - 100) + monthDay;
    })();

    // ---------- verification code: digits only ----------
    document.getElementById("verificationCode").addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "");
    });

    // ---------- no copy / paste on password fields ----------
    let lastClipboardToast = 0;

    ["password", "confirmPassword"].forEach(function (id) {
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

    // ---------- live password checklist ----------
    const passwordRules = {
        length: function (v) { return v.length >= 8; },
        upper: function (v) { return /[A-Z]/.test(v); },
        lower: function (v) { return /[a-z]/.test(v); },
        number: function (v) { return /\d/.test(v); },
        special: function (v) { return /[^A-Za-z0-9]/.test(v); },
        nospace: function (v) { return v !== "" && !/\s/.test(v); },
        nouser: function (v) {
            const u = document.getElementById("username").value.trim().toLowerCase();
            return v !== "" && (u.length < 3 || !v.toLowerCase().includes(u));
        }
    };

    function updatePasswordMatch() {
        const password = document.getElementById("password").value;
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
        const value = document.getElementById("password").value;

        document.querySelectorAll("#passwordRules li").forEach(function (li) {
            const ok = passwordRules[li.dataset.rule](value);
            li.classList.toggle("text-success", ok);
            li.classList.toggle("text-muted", !ok);
            li.querySelector(".rule-icon").textContent = ok ? "✓" : "○";
        });

        updatePasswordMatch();
    }

    document.getElementById("password").addEventListener("input", updatePasswordChecklist);
    document.getElementById("username").addEventListener("input", updatePasswordChecklist);
    document.getElementById("confirmPassword").addEventListener("input", updatePasswordMatch);

    // ---------- waiver modal ----------
    const waiverDialog = document.getElementById("waiverDialog");
    const waiverBody = document.getElementById("waiverBody");
    const agreeWaiverButton = document.getElementById("agreeWaiverButton");
    const waiverHint = document.getElementById("waiverHint");
    const waiverCheck = document.getElementById("waiverCheck");
    const waiverField = document.getElementById("waiverAgreement");
    let waiverRead = false;

    function checkWaiverScrolled() {
        if (waiverRead) {
            return;
        }

        const atBottom = waiverBody.scrollTop + waiverBody.clientHeight >= waiverBody.scrollHeight - 8;

        if (atBottom) {
            waiverRead = true;
            agreeWaiverButton.disabled = false;
            waiverHint.classList.add("d-none");
        }
    }

    function openWaiver() {
        waiverDialog.showModal();
        waiverBody.scrollTop = 0;
        checkWaiverScrolled();
    }

    document.getElementById("openWaiverButton").addEventListener("click", openWaiver);
    waiverBody.addEventListener("scroll", checkWaiverScrolled);

    document.getElementById("closeWaiverButton").addEventListener("click", function () {
        waiverDialog.close();
    });
    document.getElementById("closeWaiverX").addEventListener("click", function () {
        waiverDialog.close();
    });
    waiverDialog.addEventListener("click", function (event) {
        if (event.target === waiverDialog) {
            waiverDialog.close();
        }
    });

    agreeWaiverButton.addEventListener("click", function () {
        waiverField.value = "1";
        waiverCheck.checked = true;
        waiverDialog.close();
    });

    // Ticking the box opens the waiver first. Unticking takes the agreement back.
    waiverCheck.addEventListener("click", function (event) {
        if (waiverCheck.checked) {
            event.preventDefault();
            openWaiver();
        } else {
            waiverField.value = "";
        }
    });

    applyRegistrantType();

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