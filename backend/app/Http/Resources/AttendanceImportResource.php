<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'imported_by' => StaffSummaryResource::make($this->whenLoaded('importedBy')),
            'dates' => $this->dates,
            'records_count' => $this->records_count,
            'unmatched' => $this->unmatched,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
