<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'staff_id', 'date', 'check_in', 'check_out', 'late',
        'source', 'reason', 'corrected_by', 'import_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'late' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'corrected_by');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class, 'import_id');
    }
}
