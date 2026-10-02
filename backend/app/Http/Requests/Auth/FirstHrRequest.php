<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creates the first HR account, only allowed while the staff table is empty
 * (checked in the controller, not here — that's a state check, not shape).
 */
class FirstHrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'emp_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:4'],
        ];
    }
}
