<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveHistory extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['leave_application_id', 'by_staff_id', 'action', 'comment'];

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function byStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'by_staff_id');
    }
}
