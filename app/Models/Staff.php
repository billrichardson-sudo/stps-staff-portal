<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'staff';

    public const ROLES = [
        'staff' => 'Staff',
        'sectional_head' => 'Sectional Head',
        'supervisor' => 'Support Staff Supervisor',
        'headmaster' => 'Headmaster',
        'hr' => 'HR',
    ];

    protected $fillable = [
        'emp_id', 'bio_id', 'name', 'designation', 'category', 'department',
        'role', 'first_approver_id', 'appointment_date', 'email', 'mobile',
        'status', 'password', 'must_change_password', 'delegate_id', 'delegate_until',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'appointment_date' => 'date',
            'delegate_until' => 'date',
            'must_change_password' => 'boolean',
        ];
    }

    public function firstApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'first_approver_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'delegate_id');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function isDelegateActiveFor(Staff $approver): bool
    {
        return $approver->delegate_id === $this->id
            && $approver->delegate_until !== null
            && $approver->delegate_until->greaterThanOrEqualTo(today());
    }
}
