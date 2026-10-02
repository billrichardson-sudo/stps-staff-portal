<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApprovalStep extends Model
{
    protected $fillable = [
        'leave_application_id', 'step_order', 'kind', 'label',
        'approver_id', 'status', 'acted_by', 'acted_at', 'comment',
    ];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approver_id');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'acted_by');
    }
}
