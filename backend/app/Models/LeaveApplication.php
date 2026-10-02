<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveApplication extends Model
{
    protected $fillable = [
        'staff_id', 'type', 'from_date', 'to_date', 'days', 'reason',
        'start_time', 'end_time', 'certificate_path', 'status', 'current_step',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'days' => 'decimal:1',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LeaveApprovalStep::class)->orderBy('step_order');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LeaveHistory::class)->orderBy('created_at');
    }

    public function currentStep(): ?LeaveApprovalStep
    {
        return $this->steps->firstWhere('step_order', $this->current_step);
    }
}
