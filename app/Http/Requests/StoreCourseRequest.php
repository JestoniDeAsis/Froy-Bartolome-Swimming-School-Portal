<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'course_name'          => ['required', 'string', 'max:150'],
            'course_session_count' => ['required', 'integer', 'min:1'],
            'course_price'         => ['required', 'numeric', 'min:0'],

            // Category/skill contents are validated field-by-field here;
            // duplicate names and the id-must-exist checks are handled in
            // the controller since they depend on what's already in the DB.
            'categories'           => ['nullable', 'array'],
            'categories.*.id'      => ['nullable'],
            'categories.*.name'    => ['nullable', 'string', 'max:150'],
            'categories.*.deleted' => ['nullable', 'boolean'],
            'categories.*.skills'  => ['nullable', 'array'],
        ];
    }
}