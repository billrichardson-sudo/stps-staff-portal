<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal staff shape for embedding inside other resources
 * (approver, applicant, "acted by") without pulling in everything.
 */
class StaffSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emp_id' => $this->emp_id,
            'name' => $this->name,
            'designation' => $this->designation,
            'category' => $this->category,
            'role' => $this->role,
        ];
    }
}
