<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Staff;
use Illuminate\Support\Carbon;

class AttendanceService
{
    protected ?array $uploadedDatesCache = null;

    /** @return string[] every date (Y-m-d) covered by a fingerprint import */
    public function uploadedDates(): array
    {
        if ($this->uploadedDatesCache !== null) {
            return $this->uploadedDatesCache;
        }

        $all = [];
        foreach (AttendanceImport::pluck('dates') as $dates) {
            foreach ($dates as $date) {
                $all[$date] = true;
            }
        }

        return $this->uploadedDatesCache = array_keys($all);
    }

    public function isUploaded(string $date): bool
    {
        return in_array($date, $this->uploadedDates(), true);
    }

    public function latestUpload(): ?string
    {
        $dates = $this->uploadedDates();

        return $dates ? max($dates) : null;
    }

    /** Working days in the last $days (including today) with no import yet. */
    public function missingUploads(int $days): array
    {
        $out = [];
        $today = today();

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->subDays($i);
            $dateStr = $date->toDateString();
            if (! $date->isWeekend() && ! Holiday::whereDate('date', $dateStr)->exists() && ! $this->isUploaded($dateStr)) {
                $out[] = $dateStr;
            }
        }

        return $out;
    }

    /**
     * The status shown for one staff member on one day: approved leave wins
     * first, then weekend, then holiday, then the fingerprint scan, then
     * "Absent" — but only once that date has actually been uploaded.
     *
     * @return array{0: string, 1: string} [label, color] where color is one of ok|warn|bad|idle|info
     */
    public function dayStatus(Staff $staff, Carbon $date): array
    {
        $dateStr = $date->toDateString();

        $coveringLeave = LeaveApplication::where('staff_id', $staff->id)
            ->where('from_date', '<=', $dateStr)
            ->where('to_date', '>=', $dateStr)
            ->get();

        $granted = $coveringLeave->first(fn ($l) => $l->status === 'granted' && $l->type !== 'Short');
        if ($granted) {
            $label = (float) $granted->days === 0.5 ? "{$granted->type} Leave (half day)" : "{$granted->type} Leave";

            return [$label, 'info'];
        }

        if ($date->isWeekend()) {
            return ['Weekend', 'idle'];
        }

        $holiday = Holiday::whereDate('date', $dateStr)->first();
        if ($holiday) {
            return ["Holiday — {$holiday->name}", 'idle'];
        }

        $settings = AppSetting::current();
        $attendance = Attendance::where('staff_id', $staff->id)->where('date', $dateStr)->first();
        $shortGranted = $coveringLeave->first(fn ($l) => $l->status === 'granted' && $l->type === 'Short');

        if ($attendance && ($attendance->check_in || $attendance->check_out)) {
            $lateTime = $settings->lateTimeFor($staff->category);
            $isLate = $attendance->late ?? ($attendance->check_in && $attendance->check_in > $lateTime);
            $label = $isLate ? 'Late' : 'Present';
            $color = $isLate ? 'warn' : 'ok';

            if ($attendance->check_in && $attendance->check_out && $attendance->check_out < $settings->half_day_before) {
                $label = 'Half Day';
                $color = 'warn';
            }
            if (! $attendance->check_in) {
                $label = 'Present (no check-in scan)';
                $color = 'warn';
            }
            if ($shortGranted) {
                $label .= ' + Short Leave';
            }

            return [$label, $color];
        }

        if ($coveringLeave->contains(fn ($l) => in_array($l->status, ['pending', 'clarify']))) {
            return ['Leave pending', 'warn'];
        }

        if ($this->isUploaded($dateStr)) {
            return ['Absent', 'bad'];
        }

        if ($dateStr <= today()->toDateString()) {
            return ['Not uploaded yet', 'idle'];
        }

        return ['—', 'idle'];
    }
}
