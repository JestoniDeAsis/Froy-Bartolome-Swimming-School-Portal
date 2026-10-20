<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegisterRequest extends FormRequest
{
    // Letters (including ñ, é, etc.), spaces, periods, apostrophes, and hyphens. Must start with a letter.
    private const NAME_REGEX = '/^\p{L}[\p{L} .\'’-]*$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_handler' => ['required', Rule::in(['parent_guardian', 'self'])],

            'username' => ['required', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'unique:tbl_users,username'],
            'email_address' => ['required', 'email', 'max:100', 'unique:tbl_users,email_address'],

            'password' => [
                'required',
                'max:64',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
                'same:confirm_password',
            ],

            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:' . self::NAME_REGEX],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
            'name_suffix' => ['nullable', Rule::in(['', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V'])],

            'birthdate' => [
                'required',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    $age = Carbon::parse($value)->age;

                    if ($this->input('account_handler') === 'self' && $age < 18) {
                        $fail('You must be at least 18 years old to register for yourself. Ask a parent or guardian to register you.');
                        return;
                    }

                    if ($age < 5) {
                        $fail('The applicant must be at least 5 years old to be registered.');
                    }
                },
            ],

            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'phone_number' => ['required', 'regex:/^09\d{9}$/'],
            'address' => ['required', 'string', 'max:255'],

            'parent_guardian_fullname' => ['required', 'string', 'min:2', 'max:100', 'regex:' . self::NAME_REGEX],
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
            'account_handler.required' => 'Please choose who is registering.',
            'account_handler.in' => 'Please choose who is registering.',

            'username.regex' => 'Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.',
            'username.unique' => 'Username or email address is already registered.',
            'email_address.unique' => 'Username or email address is already registered.',
            'email_address.max' => 'Email address must not be longer than 100 characters.',

            'password.regex' => 'Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.',
            'password.same' => 'Passwords do not match.',
            'password.max' => 'Password must not be longer than 64 characters.',

            'first_name.regex' => 'First name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'last_name.regex' => 'Last name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'parent_guardian_fullname.regex' => 'Full name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'parent_guardian_fullname.min' => 'Full name must be at least 2 characters.',

            'address.required' => 'Please enter the home address.',
            'phone_number.regex' => 'Please enter a valid 11-digit Philippine mobile number.',
            'parent_guardian_phone_number.regex' => 'Please enter a valid Philippine mobile number for the parent/guardian or emergency contact.',
            'waiver_agreement.in' => 'You must agree to the waiver before registering.',
        ];
    }
}