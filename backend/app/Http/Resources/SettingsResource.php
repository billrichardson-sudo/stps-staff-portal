<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape of the school-day / headmaster-approver portion of settings.
 * Wrapped by the controller together with LeavePolicyResource and
 * HolidayResource into the full /api/settings payload.
 */
class SettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'headmaster_approver' => StaffSummaryResource::make($this->whenLoaded('headmasterApprover')),
            'late_tutorial' => $this->late_tutorial,
            'late_support' => $this->late_support,
            'late_admin' => $this->late_admin,
            'half_day_before' => $this->half_day_before,
        ];
    }
}
