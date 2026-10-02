<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'max:255'],
            'bio_id' => ['nullable', 'string', 'max:50', Rule::unique('staff', 'bio_id')->ignore($staff)],
            'designation' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(['Tutorial', 'Support', 'Admin'])],
            'role' => ['required', Rule::in(array_keys(\App\Models\Staff::ROLES))],
            'first_approver_id' => ['nullable', Rule::exists('staff', 'id')],
            'appointment_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'password' => ['nullable', 'string', 'min:4'],
        ];
    }
}
