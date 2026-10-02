<?php

namespace App\Http\Resources;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emp_id' => $this->emp_id,
            'bio_id' => $this->bio_id,
            'name' => $this->name,
            'designation' => $this->designation,
            'category' => $this->category,
            'department' => $this->department,
            'role' => $this->role,
            'role_label' => Staff::ROLES[$this->role] ?? $this->role,
            'first_approver' => StaffSummaryResource::make($this->whenLoaded('firstApprover')),
            'appointment_date' => $this->appointment_date?->toDateString(),
            'email' => $this->email,
            'mobile' => $this->mobile,
            'status' => $this->status,
            'must_change_password' => (bool) $this->must_change_password,
            'delegate_id' => $this->delegate_id,
            'delegate' => StaffSummaryResource::make($this->whenLoaded('delegate')),
            'delegate_until' => $this->delegate_until?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
