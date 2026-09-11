<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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

            'categories'           => ['nullable', 'array'],
            'categories.*.id'      => ['nullable'],
            'categories.*.name'    => ['nullable', 'string', 'max:150'],
            'categories.*.deleted' => ['nullable', 'boolean'],
            'categories.*.skills'  => ['nullable', 'array'],
        ];
    }
}