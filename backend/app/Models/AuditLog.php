<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['staff_id', 'description'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
