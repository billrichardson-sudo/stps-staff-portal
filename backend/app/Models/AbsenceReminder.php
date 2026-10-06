<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceReminder extends Model
{
    protected $fillable = ['staff_id', 'date', 'deadline', 'reminded_at', 'hr_flagged_at'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'deadline' => 'date',
            'reminded_at' => 'datetime',
            'hr_flagged_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
