<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i', 'required_without:check_out'],
            'check_out' => ['nullable', 'date_format:H:i', 'required_without:check_in', 'after:check_in'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
