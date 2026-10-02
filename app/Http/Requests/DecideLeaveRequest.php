<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // whether THIS user may decide THIS application is a policy check in the controller
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'clarify'])],
            'comment' => [Rule::requiredIf(in_array($this->input('action'), ['reject', 'clarify'], true)), 'nullable', 'string', 'max:2000'],
        ];
    }
}
