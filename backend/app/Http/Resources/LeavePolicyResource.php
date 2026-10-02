<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeavePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category' => $this->category,
            'casual_days' => (float) $this->casual_days,
            'medical_days' => (float) $this->medical_days,
            'short_leave_free' => $this->short_leave_free,
            'short_leave_deduct' => (float) $this->short_leave_deduct,
            'requires_first_approver' => (bool) $this->requires_first_approver,
            'requires_headmaster' => (bool) $this->requires_headmaster,
        ];
    }
}
