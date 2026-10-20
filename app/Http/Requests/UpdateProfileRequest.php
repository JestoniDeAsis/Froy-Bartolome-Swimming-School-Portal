<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    // Same name rule as RegisterRequest: letters (including ñ, é, etc.), spaces,
    // periods, apostrophes, and hyphens. Must start with a letter.
    private const NAME_REGEX = '/^\p{L}[\p{L} .\'’-]*$/u';

    public function authorize(): bool
    {
        // Any logged-in user may edit their own profile.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Confirmed via the "Confirm Your Password" modal before the
            // form ever reaches this endpoint; verified in the controller.
            'current_password' => ['required', 'string'],

            'first_name'  => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:' . self::NAME_REGEX],
            'last_name'   => ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_REGEX],
            'name_suffix' => ['nullable', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate'   => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $date = \Illuminate\Support\Carbon::parse($value)->startOfDay();

                    if ($date->isFuture()) {
                        $fail('Birthdate cannot be in the future.');
                        return;
                    }

                    if ($date->diffInYears(now()) < 5) {
                        $fail('You must be at least 5 years old to update this profile.');
                    }
                },
            ],
            'sex'          => ['required', 'in:Male,Female'],
            'phone_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'address'      => ['required', 'string', 'max:255'],

            'parent_guardian_fullname'     => ['required', 'string', 'min:2', 'max:100', 'regex:' . self::NAME_REGEX],
            'parent_guardian_relationship' => [
                'required',
                'in:Mother,Father,Wife,Husband,Partner,Sibling,Grandmother,Grandfather,Aunt,Uncle,Legal Guardian,Friend,Other',
            ],
            'parent_guardian_phone_number' => ['required', 'regex:/^09[0-9]{9}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please confirm your password to save changes.',

            'first_name.regex' => 'First name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'last_name.regex' => 'Last name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'parent_guardian_fullname.regex' => 'Full name can only contain letters, spaces, periods, apostrophes, and hyphens.',
            'parent_guardian_fullname.min' => 'Full name must be at least 2 characters.',

            'address.required' => 'Please enter your address.',

            'phone_number.regex' => 'Mobile number must start with 09 and be 11 digits.',
            'parent_guardian_phone_number.regex' => 'Mobile number must start with 09 and be 11 digits.',
        ];
    }
}