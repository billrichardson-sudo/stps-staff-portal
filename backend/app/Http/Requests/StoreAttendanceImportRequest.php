<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The BioStar report (PDF/CSV/Excel) is parsed and column-mapped in the
 * browser, same as the existing prototype. The backend receives the
 * already-resolved records to commit, keyed by staff_id.
 */
class StoreAttendanceImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.staff_id' => ['required', 'integer', 'exists:staff,id'],
            'records.*.date' => ['required', 'date'],
            'records.*.check_in' => ['nullable', 'date_format:H:i'],
            'records.*.check_out' => ['nullable', 'date_format:H:i'],
            'records.*.late' => ['nullable', 'boolean'],
            'unmatched' => ['nullable', 'array'],
            'unmatched.*' => ['string'],
        ];
    }
}
