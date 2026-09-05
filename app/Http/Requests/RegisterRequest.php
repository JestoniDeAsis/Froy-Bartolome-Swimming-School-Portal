<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'unique:tbl_users,username'],
            'email_address' => ['required', 'email', 'unique:tbl_users,email_address'],

            'password' => [
                'required',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
                'same:confirm_password',
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
                    if (Carbon::parse($value)->age < 5) {
                        $fail('The applicant must be at least 5 years old to be registered.');
                    }
                },
            ],

            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'phone_number' => ['required', 'regex:/^09\d{9}$/'],

            'parent_guardian_fullname' => ['required', 'string', 'max:255'],
            'parent_guardian_relationship' => ['required', Rule::in([
                'Mother', 'Father', 'Wife', 'Husband', 'Partner', 'Sibling',
                'Grandmother', 'Grandfather', 'Aunt', 'Uncle',
                'Legal Guardian', 'Friend', 'Other',
            ])],
            'parent_guardian_phone_number' => ['required', 'regex:/^09\d{9}$/'],

            'waiver_agreement' => ['required', 'in:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.',
            'username.unique' => 'Username or email address is already registered.',
            'email_address.unique' => 'Username or email address is already registered.',
            'password.regex' => 'Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.',
            'password.same' => 'Passwords do not match.',
            'phone_number.regex' => 'Please enter a valid 11-digit Philippine mobile number.',
            'parent_guardian_phone_number.regex' => 'Please enter a valid Parent/Guardian Philippine mobile number.',
            'waiver_agreement.in' => 'You must agree to the waiver before registering.',
        ];
    }
}