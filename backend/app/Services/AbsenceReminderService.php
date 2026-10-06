<?php

namespace App\Services;

use App\Models\AbsenceReminder;
use App\Models\Notification;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reminds staff to apply for leave when fingerprint attendance shows them
 * absent with nothing on file, and flags HR once the grace period passes
 * with the absence still unexplained — so it can be reviewed for no-pay
 * leave by a human rather than decided automatically by the system.
 */
class AbsenceReminderService
{
    /** Days of grace before an unexplained absence is flagged to HR. */
    const DEADLINE_DAYS = 7;

    public function __construct(protected AttendanceService $attendanceService) {}

    /**
     * Called right after HR uploads fingerprint attendance. For each
     * uploaded date, staff who come out "Absent" (no scan, no covering leave
     * application of any status) are reminded once — re-uploading the same
     * date doesn't send a second reminder, and a staff member who no longer
     * reads as Absent (e.g. they've since applied for leave) has any open
     * reminder cleared.
     */
    public function checkNewAbsences(array $dates): void
    {
        $staffList = Staff::where('status', 'Active')->get();

        foreach ($dates as $dateStr) {
            $date = Carbon::parse($dateStr);

            foreach ($staffList as $staff) {
                [$status] = $this->attendanceService->dayStatus($staff, $date);
                $existing = AbsenceReminder::where('staff_id', $staff->id)->where('date', $dateStr)->first();

                if ($status !== 'Absent') {
                    $existing?->delete();

                    continue;
                }

                if ($existing) {
                    continue;
                }

                $deadline = $date->copy()->addDays(self::DEADLINE_DAYS);

                AbsenceReminder::create([
                    'staff_id' => $staff->id,
                    'date' => $dateStr,
                    'deadline' => $deadline->toDateString(),
                    'reminded_at' => now(),
                ]);

                Notification::create([
                    'staff_id' => $staff->id,
                    'title' => 'Please apply for leave',
                    'message' => "You were marked absent on {$date->format('j M Y')} with no leave application on file. Please apply for leave by {$deadline->format('j M Y')} — if nothing is on file by then, it may need to be treated as unpaid leave.",
                ]);
            }
        }
    }

    /**
     * Re-checks absence reminders whose deadline has passed. One that now
     * has a leave application on file (or corrected attendance) is resolved
     * and removed; the rest are flagged to HR once — not on every dashboard
     * load. Returns the current full list of unresolved overdue absences.
     *
     * @return Collection<int, AbsenceReminder>
     */
    public function flagOverdueAbsences(): Collection
    {
        $overdue = AbsenceReminder::with('staff')
            ->where('deadline', '<=', today()->toDateString())
            ->get();

        $stillOpen = collect();
        $toNotify = collect();

        foreach ($overdue as $reminder) {
            if (! $reminder->staff) {
                $reminder->delete();

                continue;
            }

            [$status] = $this->attendanceService->dayStatus($reminder->staff, $reminder->date);
            if ($status !== 'Absent') {
                $reminder->delete();

                continue;
            }

            if (! $reminder->hr_flagged_at) {
                $toNotify->push($reminder);
            }

            $stillOpen->push($reminder);
        }

        if ($toNotify->isNotEmpty()) {
            $this->notifyHr($toNotify);
            $toNotify->each(fn (AbsenceReminder $r) => $r->update(['hr_flagged_at' => now()]));
        }

        return $stillOpen;
    }

    protected function notifyHr(Collection $reminders): void
    {
        $count = $reminders->count();
        $names = $reminders->map(fn (AbsenceReminder $r) => "{$r->staff->name} ({$r->date->format('j M')})")->join(', ');
        $title = 'Unexplained absence needs review';
        $message = $count === 1
            ? "{$names} has an unexplained absence past the 7-day deadline with no leave application. Review it in the Staff dashboard."
            : "{$count} staff have unexplained absences past the 7-day deadline with no leave application: {$names}. Review them in the Staff dashboard.";

        $hrUsers = Staff::where('role', 'hr')->get();
        if ($hrUsers->isEmpty()) {
            $headmaster = Staff::where('role', 'headmaster')->first();
            if ($headmaster) {
                Notification::create(['staff_id' => $headmaster->id, 'title' => $title, 'message' => $message]);
            }

            return;
        }

        $hrUsers->each(fn (Staff $hr) => Notification::create(['staff_id' => $hr->id, 'title' => $title, 'message' => $message]));
    }
}
