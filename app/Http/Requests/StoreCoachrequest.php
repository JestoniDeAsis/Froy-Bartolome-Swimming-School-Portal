<?php

namespace App\Http\Requests;

use App\Http\Controllers\CoachController;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CoachController::userIsAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'unique:tbl_users,username'],
            'email_address' => ['required', 'email', 'unique:tbl_users,email_address'],

            // Same strength rule as public registration.
            'password' => [
                'required',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
            ],

            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_suffix' => ['nullable', Rule::in(['', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V'])],

            'birthdate' => [
                'required',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->age < CoachController::MIN_COACH_AGE) {
                        $fail('A coach must be at least ' . CoachController::MIN_COACH_AGE . ' years old.');
                    }
                },
            ],

            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'phone_number' => ['required', 'regex:/^09\d{9}$/'],

            // Emergency contact is stored in the same parent_guardian_* columns
            // that public registration uses.
            'parent_guardian_fullname' => ['required', 'string', 'max:255'],
            'parent_guardian_relationship' => ['required', Rule::in([
                'Mother', 'Father', 'Wife', 'Husband', 'Partner', 'Sibling',
                'Grandmother', 'Grandfather', 'Aunt', 'Uncle',
                'Legal Guardian', 'Friend', 'Other',
            ])],
            'parent_guardian_phone_number' => ['required', 'regex:/^09\d{9}$/'],

            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['integer', 'distinct', 'exists:' . CoachController::COURSES_TABLE . ',' . CoachController::COURSE_ID_COL],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.',
            'username.unique' => 'That username is already taken.',
            'email_address.unique' => 'That email address is already registered.',
            'password.regex' => 'Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.',
            'phone_number.regex' => 'Please enter a valid 11-digit Philippine mobile number (09XXXXXXXXX).',
            'parent_guardian_phone_number.regex' => 'Please enter a valid emergency contact mobile number (09XXXXXXXXX).',
            'courses.required' => 'Assign at least one course.',
            'courses.min' => 'Assign at least one course.',
        ];
    }
}