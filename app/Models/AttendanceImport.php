<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImport extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['file_name', 'imported_by', 'dates', 'records_count', 'unmatched'];

    protected function casts(): array
    {
        return [
            'dates' => 'array',
            'unmatched' => 'array',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'imported_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Attendance::class, 'import_id');
    }
}
