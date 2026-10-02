<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    protected $fillable = [
        'headmaster_approver_id', 'late_tutorial', 'late_support', 'late_admin', 'half_day_before',
    ];

    public function headmasterApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'headmaster_approver_id');
    }

    public static function current(): self
    {
        return self::first() ?? self::create([]);
    }

    public function lateTimeFor(string $category): string
    {
        return match ($category) {
            'Support' => $this->late_support,
            'Admin' => $this->late_admin,
            default => $this->late_tutorial,
        };
    }
}
