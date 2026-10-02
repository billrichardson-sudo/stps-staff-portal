<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApprovalStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step_order' => $this->step_order,
            'kind' => $this->kind, // first | headmaster | person | hr
            'label' => $this->label,
            'approver' => StaffSummaryResource::make($this->whenLoaded('approver')),
            'status' => $this->status, // waiting | approved | rejected
            'acted_by' => StaffSummaryResource::make($this->whenLoaded('actedBy')),
            'acted_at' => $this->acted_at?->toIso8601String(),
            'comment' => $this->comment,
        ];
    }
}
