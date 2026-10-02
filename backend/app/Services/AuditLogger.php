<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Staff;

class AuditLogger
{
    public static function log(?Staff $staff, string $description): void
    {
        AuditLog::create(['staff_id' => $staff?->id, 'description' => $description]);
    }
}
