<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'policies' => ['required', 'array'],
            'policies.Tutorial.casual_days' => ['required', 'numeric', 'min:0'],
            'policies.Tutorial.medical_days' => ['required', 'numeric', 'min:0'],
            'policies.Tutorial.short_leave_free' => ['required', 'integer', 'min:0'],
            'policies.Tutorial.short_leave_deduct' => ['required', 'numeric', 'min:0'],
            'policies.Tutorial.requires_first_approver' => ['required', 'boolean'],
            'policies.Tutorial.requires_headmaster' => ['required', 'boolean'],
            'policies.Support.casual_days' => ['required', 'numeric', 'min:0'],
            'policies.Support.medical_days' => ['required', 'numeric', 'min:0'],
            'policies.Support.short_leave_free' => ['required', 'integer', 'min:0'],
            'policies.Support.short_leave_deduct' => ['required', 'numeric', 'min:0'],
            'policies.Support.requires_first_approver' => ['required', 'boolean'],
            'policies.Support.requires_headmaster' => ['required', 'boolean'],
            'policies.Admin.casual_days' => ['required', 'numeric', 'min:0'],
            'policies.Admin.medical_days' => ['required', 'numeric', 'min:0'],
            'policies.Admin.short_leave_free' => ['required', 'integer', 'min:0'],
            'policies.Admin.short_leave_deduct' => ['required', 'numeric', 'min:0'],
            'policies.Admin.requires_first_approver' => ['required', 'boolean'],
            'policies.Admin.requires_headmaster' => ['required', 'boolean'],
            'headmaster_approver_id' => ['nullable', Rule::exists('staff', 'id')],
            'late_tutorial' => ['required', 'date_format:H:i'],
            'late_support' => ['required', 'date_format:H:i'],
            'late_admin' => ['required', 'date_format:H:i'],
            'half_day_before' => ['required', 'date_format:H:i'],
        ];
    }
}
