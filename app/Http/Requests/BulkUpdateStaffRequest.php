<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => [Rule::exists('staff', 'id')],
            'first_approver_id' => ['nullable', 'sometimes', Rule::exists('staff', 'id')],
            'category' => ['nullable', 'sometimes', Rule::in(['Tutorial', 'Support', 'Admin'])],
        ];
    }
}
