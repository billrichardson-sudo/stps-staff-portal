<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used the first time a staff member signs in with a temporary password
 * (e.g. one created by HR, or from a fingerprint-report import).
 */
class SetFirstPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}
