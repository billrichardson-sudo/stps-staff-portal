<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'delegate_id' => ['nullable', Rule::exists('staff', 'id')],
            'delegate_until' => [Rule::requiredIf($this->filled('delegate_id')), 'nullable', 'date'],
        ];
    }
}
