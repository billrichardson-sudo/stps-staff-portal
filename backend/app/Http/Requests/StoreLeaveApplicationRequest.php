<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isShort = $this->input('type') === 'Short';

        return [
            'type' => ['required', Rule::in(['Casual', 'Medical', 'Short', 'Other'])],
            'from_date' => ['required', 'date'],
            'to_date' => [Rule::requiredIf(! $isShort), 'date', 'after_or_equal:from_date'],
            'half' => ['sometimes', 'boolean'],
            'start_time' => [Rule::requiredIf($isShort), 'date_format:H:i'],
            'end_time' => [Rule::requiredIf($isShort), 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:2000'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
