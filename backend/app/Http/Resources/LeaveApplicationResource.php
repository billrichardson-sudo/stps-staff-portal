<?php

namespace App\Http\Resources;

use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user && $this->staff_id === $user->id;

        return [
            'id' => $this->id,
            'staff' => StaffSummaryResource::make($this->whenLoaded('staff')),
            'type' => $this->type, // Casual | Medical | Short | Other
            'from_date' => $this->from_date->toDateString(),
            'to_date' => $this->to_date->toDateString(),
            'days' => (float) $this->days,
            'reason' => $this->reason,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'certificate_url' => $this->certificate_path ? Storage::url($this->certificate_path) : null,
            'status' => $this->status, // pending | clarify | granted | rejected | cancelled
            'status_label' => $this->statusLabel(),
            'current_step' => $this->current_step,
            'steps' => LeaveApprovalStepResource::collection($this->whenLoaded('steps')),
            'histories' => LeaveHistoryResource::collection($this->whenLoaded('histories')),
            'created_at' => $this->created_at?->toIso8601String(),
            // Computed for the signed-in viewer, so the client never has to
            // duplicate the approval-authorization rules to decide what to show.
            'is_owner' => $isOwner,
            'can_act' => $user && ! $isOwner && $this->relationLoaded('steps')
                ? resolve(LeaveService::class)->canAct($user, $this->resource)
                : false,
            'can_cancel' => $isOwner && in_array($this->status, ['pending', 'clarify'], true),
            'can_resubmit' => $isOwner && $this->status === 'clarify',
            'can_hr_cancel' => $user && $user->role === 'hr' && ! $isOwner && $this->status === 'granted',
        ];
    }

    protected function statusLabel(): string
    {
        return match ($this->status) {
            'granted' => 'Leave granted',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'clarify' => 'Clarification requested',
            default => $this->relationLoaded('steps') && ($step = $this->steps->firstWhere('step_order', $this->current_step))
                ? ($step->kind === 'hr' ? 'Pending HR verification' : "Pending {$step->label}")
                : 'Pending',
        };
    }
}
