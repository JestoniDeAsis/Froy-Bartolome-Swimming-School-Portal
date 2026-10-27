@extends('layouts.toast')

<style>

    .profile-card {
        max-width: 760px;
        border-radius: 14px;
        border: none;
        background-color: transparent;
    }

    .profile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .profile-header .text-muted {
        margin-bottom: 1.5rem;
    }

    .profile-header-actions {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .profile-section-title {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #495057;
        border-left: 3px solid #0d6efd;
        padding-left: 0.6rem;
        margin-bottom: 1rem;
    }

    .profile-field {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        height: 100%;
    }

    .profile-field-label {
        color: #6c757d;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.2rem;
    }

    .profile-field-value {
        font-weight: 500;
        color: #212529;
    }

    .role-badge {
        display: inline-block;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .role-badge-admin   { background: #ffe3e3; color: #c92a2a; }
    .role-badge-coach   { background: #e7f5ff; color: #1971c2; }
    .role-badge-student { background: #ebfbee; color: #2b8a3e; }
    .role-badge-athlete { background: #f3e8ff; color: #7048e8; }

    .required-mark {
        color: #dc3545;
        margin-left: 0.15rem;
    }

    .modal-field-error {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 0.35rem;
        display: none;
    }

    .modal-field-error.is-visible {
        display: block;
    }

    @media (max-width: 575.98px) {

        .modal-dialog-centered {
            min-height: calc(100% - 1rem);
            margin: 0.5rem;
        }

        .modal-footer {
            flex-wrap: nowrap;
        }

        .modal-footer .d-flex {
            width: 100%;
        }

        .modal-footer .btn {
            flex: 1;
        }

    }

    @media (max-width: 575.98px) {

        .profile-card .card-body {
            padding: 1.25rem;
        }

        .profile-header {
            flex-direction: column;
            align-items: stretch;
        }

        .profile-header h2 {
            font-size: 1.5rem;
        }

        .profile-header-actions {
            width: 100%;
        }

        .profile-header-actions .btn {
            flex: 1;
        }

        #profileEditForm .profile-button-row {
            flex-direction: column;
        }

        #profileEditForm .profile-button-row .btn {
            width: 100% !important;
        }

    }


    .toggle-password {
        display: flex;
        align-items: center;
    }

</style>

<div class="card profile-card">
<div class="card-body">

    <div class="profile-header">
        <div>
            <h2 class="mb-1">My Profile</h2>
            <div class="text-muted">Manage your account and personal details</div>
        </div>

        <div class="profile-header-actions">
            <button
                type="button"
                class="btn btn-outline-secondary"
                id="changePasswordButton"
                data-action="open-change-password"
            >
                Change Password
            </button>

            <button
                type="button"
                class="btn btn-primary"
                id="editProfileButton"
                data-action="enter-edit-mode"
            >
                Edit Profile
            </button>
        </div>
    </div>

    @if (!$profile)

        <div class="alert alert-warning">
            We couldn't load your profile. Please try refreshing the page.
        </div>

    @else

    {{-- =====================================
         VIEW MODE
    ====================================== --}}

    <div id="profileViewMode">

        <div class="profile-section-title">Account Information</div>

        <div class="row g-3 mb-4">

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Username</div>
                    <div class="profile-field-value">{{ $currentUser->username }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Email Address</div>
                    <div class="profile-field-value">{{ $currentUser->email_address }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Role</div>
                    <div class="profile-field-value">
                        <span class="role-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                    </div>
                </div>
            </div>

        </div>

        <div class="profile-section-title">Personal Information</div>

        <div class="row g-3 mb-4">

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">First Name</div>
                    <div class="profile-field-value">{{ $profile->first_name }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Middle Name</div>
                    <div class="profile-field-value">{{ $profile->middle_name ?: '—' }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Last Name</div>
                    <div class="profile-field-value">{{ $profile->last_name }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Suffix</div>
                    <div class="profile-field-value">{{ $profile->name_suffix ?: '—' }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Date of Birth</div>
                    <div class="profile-field-value">{{ $profile->birthdate }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Sex</div>
                    <div class="profile-field-value">{{ $profile->sex }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Mobile Number</div>
                    <div class="profile-field-value">{{ $profile->phone_number }}</div>
                </div>
            </div>

            <div class="col-12">
                <div class="profile-field">
                    <div class="profile-field-label">Address</div>
                    <div class="profile-field-value">{{ $profile->address ?: '—' }}</div>
                </div>
            </div>

        </div>

        <div class="profile-section-title">{{ $contactSectionLabel }}</div>

        <div class="row g-3">

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Full Name</div>
                    <div class="profile-field-value">{{ $profile->parent_guardian_fullname }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Relationship</div>
                    <div class="profile-field-value">{{ $profile->parent_guardian_relationship }}</div>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="profile-field">
                    <div class="profile-field-label">Mobile Number</div>
                    <div class="profile-field-value">{{ $profile->parent_guardian_phone_number }}</div>
                </div>
            </div>

        </div>

    </div>

    {{-- =====================================
         EDIT MODE
    ====================================== --}}

    <form id="profileEditForm" class="d-none">

        @csrf

        <div class="profile-section-title">Personal Information</div>

        <div class="mb-3">
            <label class="form-label">First Name <span class="required-mark">*</span></label>
            <input type="text" class="form-control" id="firstName" name="first_name"
                   value="{{ $profile->first_name }}" maxlength="50" data-name required>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="firstName"></span>
                <span class="ms-auto"><span data-count-for="firstName">0</span> / 50</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Middle Name</label>
            <input type="text" class="form-control" id="middleName" name="middle_name"
                   value="{{ $profile->middle_name }}" maxlength="50" data-name>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="middleName"></span>
                <span class="ms-auto"><span data-count-for="middleName">0</span> / 50</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Last Name <span class="required-mark">*</span></label>
            <input type="text" class="form-control" id="lastName" name="last_name"
                   value="{{ $profile->last_name }}" maxlength="50" data-name required>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="lastName"></span>
                <span class="ms-auto"><span data-count-for="lastName">0</span> / 50</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Name Suffix</label>
            <select class="form-select" id="nameSuffix" name="name_suffix">
                @php $suffixes = ['' => 'None', 'Jr.' => 'Jr.', 'Sr.' => 'Sr.', 'II' => 'II', 'III' => 'III', 'IV' => 'IV', 'V' => 'V']; @endphp
                @foreach ($suffixes as $value => $label)
                    <option value="{{ $value }}" @selected(($profile->name_suffix ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date of Birth <span class="required-mark">*</span></label>
            <input type="date" class="form-control" id="birthdate" name="birthdate"
                   value="{{ $profile->birthdate }}"
                   max="{{ now()->subYears(5)->format('Y-m-d') }}"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mobile Number <span class="required-mark">*</span></label>
            <input type="tel" class="form-control" id="phoneNumber" name="phone_number"
                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                   value="{{ $profile->phone_number }}" required>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="phoneNumber"></span>
                <span class="ms-auto"><span data-count-for="phoneNumber">0</span> / 11</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label d-block">Sex <span class="required-mark">*</span></label>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sex" id="sexMale" value="Male"
                           @checked(($profile->sex ?? '') === 'Male') required>
                    <label class="form-check-label" for="sexMale">Male</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sex" id="sexFemale" value="Female"
                           @checked(($profile->sex ?? '') === 'Female')>
                    <label class="form-check-label" for="sexFemale">Female</label>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Address <span class="required-mark">*</span></label>
            <textarea class="form-control" id="address" name="address" rows="2" maxlength="255"
                      placeholder="House no., street, barangay, city, province" required>{{ $profile->address }}</textarea>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="address"></span>
                <span class="ms-auto"><span data-count-for="address">0</span> / 255</span>
            </div>
        </div>

        <hr>

        <div class="profile-section-title">{{ $contactSectionLabel }}</div>

        <div class="mb-3">
            <label class="form-label">{{ $contactSectionLabel }} Full Name <span class="required-mark">*</span></label>
            <input type="text" class="form-control" id="parentGuardianFullname" name="parent_guardian_fullname"
                   value="{{ $profile->parent_guardian_fullname }}" maxlength="100" data-name required>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="parentGuardianFullname"></span>
                <span class="ms-auto"><span data-count-for="parentGuardianFullname">0</span> / 100</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Relationship <span class="required-mark">*</span></label>
            <select class="form-select" name="parent_guardian_relationship" required>
                @php
                    $relationships = [
                        'Mother', 'Father', 'Wife', 'Husband', 'Partner', 'Sibling',
                        'Grandmother', 'Grandfather', 'Aunt', 'Uncle',
                        'Legal Guardian', 'Friend', 'Other',
                    ];
                @endphp
                <option value="" disabled @selected(empty($profile->parent_guardian_relationship))>
                    Select relationship
                </option>
                @foreach ($relationships as $relationship)
                    <option value="{{ $relationship }}" @selected(($profile->parent_guardian_relationship ?? '') === $relationship)>
                        {{ $relationship }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">{{ $contactSectionLabel }} Mobile Number <span class="required-mark">*</span></label>
            <input type="tel" class="form-control" id="parentGuardianPhoneNumber" name="parent_guardian_phone_number"
                   placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                   value="{{ $profile->parent_guardian_phone_number }}" required>
            <div class="form-text d-flex justify-content-between">
                <span class="text-danger" data-msg-for="parentGuardianPhoneNumber"></span>
                <span class="ms-auto"><span data-count-for="parentGuardianPhoneNumber">0</span> / 11</span>
            </div>
        </div>

        <div class="d-flex gap-2 profile-button-row">
            <button type="button" class="btn btn-outline-danger w-50" data-action="cancel-edit-mode">
                Cancel
            </button>
            <button type="button" class="btn btn-success w-50" id="saveProfileButton" data-action="request-save-profile">
                Save Changes
            </button>
        </div>

    </form>

    @endif

</div>
</div>

{{-- =====================================
     CONFIRM PASSWORD MODAL (used before saving profile edits)
====================================== --}}

<div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-labelledby="confirmPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmPasswordModalLabel">Confirm Your Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">For your security, please enter your password to save these changes.</p>
                <label class="form-label">Password <span class="required-mark">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirmSavePassword" autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary toggle-password"
                            data-target="confirmSavePassword" aria-label="Show password" title="Show password">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <div class="modal-field-error" id="confirmSavePasswordError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSaveProfileButton" data-action="confirm-save-profile">
                    Confirm &amp; Save
                </button>
            </div>
        </div>
    </div>
</div>

{{-- =====================================
     CHANGE PASSWORD MODAL
====================================== --}}

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- STEP 1: confirm current password --}}
                <div id="passwordStepCurrent">

                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="required-mark">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="currentPassword" autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="currentPassword" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="modal-field-error" id="currentPasswordError"></div>
                    </div>

                </div>

                {{-- STEP 2: only shown once the current password checks out --}}
                <div id="passwordStepNew" class="d-none">

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="required-mark">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="newPassword" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
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

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="required-mark">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmNewPassword" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-password"
                                    data-target="confirmNewPassword" aria-label="Show password" title="Show password">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="modal-field-error" id="newPasswordError"></div>
                    </div>

                </div>

            </div>
            <div class="modal-footer">

                <div id="passwordStepCurrentFooter" class="d-flex gap-2 w-100">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary ms-auto" id="verifyCurrentPasswordButton" data-action="verify-current-password">
                        Continue
                    </button>
                </div>

                <div id="passwordStepNewFooter" class="d-none d-flex gap-2 w-100">
                    <button type="button" class="btn btn-outline-primary" data-action="cancel-password-change">Back</button>
                    <button type="button" class="btn btn-primary ms-auto" id="updatePasswordButton" data-action="update-password">
                        Update Password
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {

    // Guard against this block running more than once if the surrounding
    // markup ever gets re-injected into the page (e.g. an AJAX page swap
    // that re-fetches this partial). All the actual interaction wiring
    // below is delegated at the document level, so it only needs to be
    // attached a single time regardless of how many times the HTML itself
    // gets replaced.
    if (window.__profilePageInitialized) {
        return;
    }
    window.__profilePageInitialized = true;

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function showFieldError(el, message) {
        if (!el) return;
        el.textContent = message;
        el.classList.add('is-visible');
    }

    function clearFieldError(el) {
        if (!el) return;
        el.textContent = '';
        el.classList.remove('is-visible');
    }

    function getModal(id) {
        const el = document.getElementById(id);
        if (!el || typeof bootstrap === 'undefined') return null;
        return bootstrap.Modal.getOrCreateInstance(el);
    }

    // Programmatically hiding a modal while an element inside it still has
    // focus trips a browser aria-hidden/focus warning. Blur first so focus
    // moves off the modal before Bootstrap marks it aria-hidden.
    function hideModal(id) {
        if (document.activeElement) document.activeElement.blur();
        const modal = getModal(id);
        if (modal) modal.hide();
    }

    // Laravel returns JSON on validation errors, but a server exception
    // (bad column name, SQL error, etc.) returns an HTML error page
    // instead. response.json() throws on that HTML, which used to get
    // swallowed into a generic "something went wrong" message. This logs
    // the real response so it shows up in the browser console, which is
    // usually enough to see exactly what broke server-side.
    async function parseJsonResponse(response, context) {
        const raw = await response.text();

        try {
            return { ok: response.ok, data: JSON.parse(raw) };
        } catch (parseError) {
            console.error(
                `[${context}] Server did not return JSON (status ${response.status}). Raw response below — this is usually a PHP/SQL error, check it or storage/logs/laravel.log for the real cause:`,
                raw
            );
            return {
                ok: false,
                data: {
                    success: false,
                    message: `Server error (status ${response.status}). Check the browser console or storage/logs/laravel.log for details.`,
                },
            };
        }
    }

    /* =========================================
       EDIT / VIEW MODE
    ========================================= */

    function enterEditMode() {
        const viewMode = document.getElementById('profileViewMode');
        const editForm = document.getElementById('profileEditForm');
        if (!editForm) return;

        viewMode.classList.add('d-none');
        editForm.classList.remove('d-none');
        document.getElementById('editProfileButton').classList.add('d-none');
        updateCounters();
    }

    function cancelEditMode() {
        const viewMode = document.getElementById('profileViewMode');
        const editForm = document.getElementById('profileEditForm');

        editForm.reset();
        document.querySelectorAll('[data-msg-for]').forEach(function(el) { el.textContent = ''; });
        updateCounters();
        editForm.classList.add('d-none');
        viewMode.classList.remove('d-none');
        document.getElementById('editProfileButton').classList.remove('d-none');
    }

    // Same rule as the registration page's step-4 check: no future dates,
    // must be at least 5 years old.
    function validateBirthdate() {
        const birthdate = document.getElementById('birthdate').value;

        if (!birthdate) {
            showToast('Please enter your date of birth.', 'danger');
            return false;
        }

        const [year, month, day] = birthdate.split('-').map(Number);
        const selectedDate = new Date(year, month - 1, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate > today) {
            showToast('Birthdate cannot be in the future.', 'danger');
            return false;
        }

        const minimumBirthdate = new Date(today.getFullYear() - 5, today.getMonth(), today.getDate());
        if (selectedDate > minimumBirthdate) {
            showToast('You must be at least 5 years old to update this profile.', 'danger');
            return false;
        }

        return true;
    }

    const NAME_PATTERN = /^\p{L}[\p{L} .'’-]*$/u;

    function validateNameField(id, label, required) {
        const input = document.getElementById(id);
        const value = input.value.trim();

        if (value === '' && !required) {
            return true;
        }

        if (required && value.length < 2) {
            showToast(label + ' must be at least 2 characters.', 'danger');
            input.focus();
            return false;
        }

        if (!NAME_PATTERN.test(value)) {
            showToast(label + ' can only contain letters, spaces, periods, apostrophes, and hyphens.', 'danger');
            input.focus();
            return false;
        }

        return true;
    }

    function validateNames() {
        return validateNameField('firstName', 'First name', true)
            && validateNameField('middleName', 'Middle name', false)
            && validateNameField('lastName', 'Last name', true)
            && validateNameField('parentGuardianFullname', 'Full name', true);
    }

    function updateCounters() {
        document.querySelectorAll('[data-count-for]').forEach(function(el) {
            const input = document.getElementById(el.dataset.countFor);
            if (input) {
                el.textContent = input.value.length;
            }
        });
    }

    // Step 1 of saving: validate the visible form, then pop the
    // password-confirmation modal instead of submitting right away.
    function requestSaveProfile() {
        const form = document.getElementById('profileEditForm');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!validateBirthdate()) {
            return;
        }

        if (!validateNames()) {
            return;
        }

        const passwordInput = document.getElementById('confirmSavePassword');
        passwordInput.value = '';
        resetShowPassword();
        clearFieldError(document.getElementById('confirmSavePasswordError'));

        const modal = getModal('confirmPasswordModal');
        if (modal) {
            modal.show();
            setTimeout(() => passwordInput.focus(), 300);
        }
    }

    // Step 2 of saving: user has entered their password in the modal,
    // actually submit the profile update.
    async function confirmSaveProfile() {
        const form = document.getElementById('profileEditForm');
        const passwordInput = document.getElementById('confirmSavePassword');
        const errorEl = document.getElementById('confirmSavePasswordError');
        clearFieldError(errorEl);

        if (!passwordInput.value) {
            showFieldError(errorEl, 'Please enter your password.');
            return;
        }

        const button = document.getElementById('confirmSaveProfileButton');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            const formData = new FormData(form);
            formData.append('current_password', passwordInput.value);

            // Adjust this URL to wherever you register the POST route
            // for ProfileController@update in web.php.
            const response = await fetch('{{ url('/profile/update') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            });

            const { ok, data } = await parseJsonResponse(response, 'profile update');

            if (ok && data.success) {

                hideModal('confirmPasswordModal');

                showToast(data.message, 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);

            } else {

                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : null;

                showFieldError(errorEl, firstError || data.message || 'Something went wrong while saving your profile.');

                button.disabled = false;
                button.textContent = 'Confirm & Save';

            }

        } catch (error) {

            console.error(error);
            showFieldError(errorEl, 'Something went wrong while saving your profile.');
            button.disabled = false;
            button.textContent = 'Confirm & Save';

        }
    }

    function restrictToDigits(input) {
        if (!input) return;

        input.addEventListener('input', function() {
            const digitsOnly = input.value.replace(/\D/g, '').slice(0, 11);
            if (digitsOnly !== input.value) {
                input.value = digitsOnly;
            }
        });
    }

    /* =========================================
       CHANGE PASSWORD (now inside a modal)
    ========================================= */

    function openChangePassword() {
        resetPasswordSteps();
        const modal = getModal('changePasswordModal');
        if (modal) modal.show();
    }

    function setPasswordVisible(button, input, show) {
        input.type = show ? 'text' : 'password';
        button.querySelector('.icon-eye').classList.toggle('d-none', show);
        button.querySelector('.icon-eye-off').classList.toggle('d-none', !show);
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        button.title = show ? 'Hide password' : 'Show password';
    }

    // Put every password field back to hidden with the closed-eye icon.
    function resetShowPassword() {
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            const input = document.getElementById(button.dataset.target);
            if (input) setPasswordVisible(button, input, false);
        });
    }

    function resetPasswordSteps() {
        const currentStep = document.getElementById('passwordStepCurrent');
        const newStep = document.getElementById('passwordStepNew');
        const currentFooter = document.getElementById('passwordStepCurrentFooter');
        const newFooter = document.getElementById('passwordStepNewFooter');
        if (!currentStep || !newStep) return;

        resetShowPassword();

        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';
        clearFieldError(document.getElementById('currentPasswordError'));
        clearFieldError(document.getElementById('newPasswordError'));

        newStep.classList.add('d-none');
        currentStep.classList.remove('d-none');
        newFooter.classList.add('d-none');
        newFooter.classList.remove('d-flex');
        currentFooter.classList.remove('d-none');
        currentFooter.classList.add('d-flex');
    }

    async function verifyCurrentPassword() {
        const currentPasswordInput = document.getElementById('currentPassword');
        const errorEl = document.getElementById('currentPasswordError');
        const currentPassword = currentPasswordInput.value;
        clearFieldError(errorEl);

        if (!currentPassword) {
            showFieldError(errorEl, 'Please enter your current password.');
            return;
        }

        const button = document.getElementById('verifyCurrentPasswordButton');
        button.disabled = true;
        button.textContent = 'Checking...';

        try {
            const formData = new FormData();
            formData.append('current_password', currentPassword);

            // Adjust this URL to wherever you register the POST route
            // for ChangePasswordController@verifyCurrent in web.php.
            const response = await fetch('{{ url('/profile/password/verify') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const { data } = await parseJsonResponse(response, 'verify current password');

            if (data.success) {
                document.getElementById('passwordStepCurrent').classList.add('d-none');
                document.getElementById('passwordStepNew').classList.remove('d-none');
                document.getElementById('passwordStepCurrentFooter').classList.add('d-none');
                document.getElementById('passwordStepCurrentFooter').classList.remove('d-flex');
                document.getElementById('passwordStepNewFooter').classList.remove('d-none');
                document.getElementById('passwordStepNewFooter').classList.add('d-flex');
                document.getElementById('newPassword').focus();
            } else {
                showFieldError(errorEl, data.message ?? 'Current password is incorrect.');
            }

        } catch (error) {

            console.error(error);
            showFieldError(errorEl, 'Something went wrong while checking your password.');

        } finally {

            button.disabled = false;
            button.textContent = 'Continue';

        }
    }

    async function updatePassword() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmNewPassword = document.getElementById('confirmNewPassword').value;
        const errorEl = document.getElementById('newPasswordError');
        clearFieldError(errorEl);

        // Same complexity rule as the registration page's step-3 check.
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

        if (!passwordPattern.test(newPassword)) {
            showFieldError(errorEl, 'Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.');
            document.getElementById('newPassword').focus();
            return;
        }

        if (newPassword !== confirmNewPassword) {
            showFieldError(errorEl, 'Passwords do not match.');
            document.getElementById('confirmNewPassword').focus();
            return;
        }

        const button = document.getElementById('updatePasswordButton');
        button.disabled = true;
        button.textContent = 'Updating...';

        try {
            const formData = new FormData();
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmNewPassword);

            // Adjust this URL to wherever you register the POST route
            // for ChangePasswordController@update in web.php.
            const response = await fetch('{{ url('/profile/password/update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const { ok, data } = await parseJsonResponse(response, 'update password');

            if (ok && data.success) {
                showToast('Password updated successfully.', 'success');
                hideModal('changePasswordModal');
            } else {

                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : null;

                showFieldError(errorEl, firstError || data.message || 'Something went wrong while updating your password.');

            }

        } catch (error) {

            console.error(error);
            showFieldError(errorEl, 'Something went wrong while updating your password.');

        } finally {

            button.disabled = false;
            button.textContent = 'Update Password';

        }
    }

    /* =========================================
       WIRING — delegated listeners, attached once.
       These work no matter how the surrounding HTML
       was inserted into the page (full load or AJAX).
    ========================================= */

    document.addEventListener('click', function(event) {
        const target = event.target.closest('[data-action]');
        if (!target) return;

        switch (target.dataset.action) {
            case 'enter-edit-mode': enterEditMode(); break;
            case 'cancel-edit-mode': cancelEditMode(); break;
            case 'request-save-profile': requestSaveProfile(); break;
            case 'confirm-save-profile': confirmSaveProfile(); break;
            case 'open-change-password': openChangePassword(); break;
            case 'verify-current-password': verifyCurrentPassword(); break;
            case 'update-password': updatePassword(); break;
            case 'cancel-password-change': resetPasswordSteps(); break;
        }
    });

    // Eye buttons: show / hide the password field named in data-target.
    document.addEventListener('click', function(event) {
        const button = event.target.closest('.toggle-password');
        if (!button) return;

        const input = document.getElementById(button.dataset.target);
        if (!input) return;

        setPasswordVisible(button, input, input.type === 'password');
    });

    document.addEventListener('input', function(event) {
        const input = event.target;

        if (input.id === 'phoneNumber' || input.id === 'parentGuardianPhoneNumber') {
            const digitsOnly = input.value.replace(/\D/g, '').slice(0, 11);
            if (digitsOnly !== input.value) {
                input.value = digitsOnly;
            }
        }

        // Names: no numbers or symbols, must start with a letter
        if (input.hasAttribute && input.hasAttribute('data-name')) {
            const cleaned = input.value
                .replace(/[^\p{L} .'’-]/gu, '')
                .replace(/^[^\p{L}]+/u, '')
                .replace(/ {2,}/g, ' ');
            const msgEl = document.querySelector('[data-msg-for="' + input.id + '"]');

            if (cleaned !== input.value) {
                input.value = cleaned;
                if (msgEl) msgEl.textContent = 'Only letters, spaces, periods, apostrophes, and hyphens are allowed.';
            } else if (msgEl) {
                msgEl.textContent = '';
            }
        }

        updateCounters();
    });

    // Reset the change-password modal whenever it's closed, so a
    // reopened modal always starts back at step 1.
    document.addEventListener('hidden.bs.modal', function(event) {
        resetShowPassword();

        if (event.target.id === 'changePasswordModal') {
            resetPasswordSteps();
        }
    });

})();
</script>