<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = [
        'category', 'casual_days', 'medical_days',
        'short_leave_free', 'short_leave_deduct',
        'requires_first_approver', 'requires_headmaster',
    ];

    protected function casts(): array
    {
        return [
            'casual_days' => 'decimal:1',
            'medical_days' => 'decimal:1',
            'short_leave_deduct' => 'decimal:2',
            'requires_first_approver' => 'boolean',
            'requires_headmaster' => 'boolean',
        ];
    }
}
