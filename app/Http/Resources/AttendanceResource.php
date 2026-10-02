<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff' => StaffSummaryResource::make($this->whenLoaded('staff')),
            'date' => $this->date->toDateString(),
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'late' => $this->late,
            'source' => $this->source, // fingerprint | manual
            'reason' => $this->reason,
            'corrected_by' => StaffSummaryResource::make($this->whenLoaded('correctedBy')),
        ];
    }
}
