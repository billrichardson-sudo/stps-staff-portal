<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalStep;
use App\Models\LeavePolicy;
use App\Models\Notification;
use App\Models\Staff;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class LeaveService
{
    public function policyFor(Staff $staff): LeavePolicy
    {
        return LeavePolicy::where('category', $staff->category)->first()
            ?? LeavePolicy::where('category', 'Tutorial')->firstOrFail();
    }

    public function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    public function isHoliday(Carbon $date): bool
    {
        return Holiday::whereDate('date', $date)->exists();
    }

    public function workDays(Carbon $from, Carbon $to): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($from, $to) as $day) {
            if (! $this->isWeekend($day) && ! $this->isHoliday($day)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Build the ordered approval chain for a leave application, based on the
     * applicant's staff category (Tutorial/Support/Admin) and the configured
     * routes. Staff cannot choose or skip approvers — this is fully determined
     * by their category and the school's settings.
     *
     * @return array<int, array{kind: string, label: string, approver_id: ?int}>
     */
    public function buildSteps(Staff $staff): array
    {
        $settings = AppSetting::current();
        $steps = [];

        if ($staff->role === 'headmaster') {
            if ($settings->headmaster_approver_id) {
                $approver = Staff::find($settings->headmaster_approver_id);
                if ($approver) {
                    $steps[] = ['kind' => 'person', 'label' => $approver->name, 'approver_id' => $approver->id];
                }
            }
            $steps[] = ['kind' => 'hr', 'label' => 'HR verification', 'approver_id' => null];

            return $steps;
        }

        $policy = $this->policyFor($staff);
        $headmaster = Staff::where('role', 'headmaster')->first();

        if ($policy->requires_first_approver) {
            $approver = $staff->first_approver_id ? Staff::find($staff->first_approver_id) : null;
            if ($approver && $approver->id !== $staff->id && $approver->isActive()) {
                $steps[] = [
                    'kind' => 'first',
                    'label' => Staff::ROLES[$approver->role] ?? 'Supervisor',
                    'approver_id' => $approver->id,
                ];
            }
        }

        if ($policy->requires_headmaster && $headmaster && $headmaster->id !== $staff->id) {
            $steps[] = ['kind' => 'headmaster', 'label' => 'Headmaster', 'approver_id' => $headmaster->id];
        }

        $steps[] = ['kind' => 'hr', 'label' => 'HR verification', 'approver_id' => null];

        return $steps;
    }

    /**
     * Leave balances for a staff member in a given year, including the
     * short-leave-to-casual deduction rule: beyond the free short leaves
     * per month, each extra short leave deducts a fraction of a casual day.
     */
    public function balances(Staff $staff, ?int $year = null, ?string $yearMonth = null): array
    {
        $year ??= now()->year;
        $yearMonth ??= now()->format('Y-m');
        $policy = $this->policyFor($staff);

        $applications = LeaveApplication::where('staff_id', $staff->id)
            ->whereYear('from_date', $year)
            ->get();

        $granted = $applications->where('status', 'granted');
        $pending = $applications->whereIn('status', ['pending', 'clarify']);

        $sum = fn ($collection, $type) => (float) $collection->where('type', $type)->sum('days');

        $shortByMonth = $granted->where('type', 'Short')
            ->groupBy(fn ($leave) => $leave->from_date->format('Y-m'))
            ->map->count();

        $deduct = 0;
        foreach ($shortByMonth as $count) {
            $deduct += max(0, $count - $policy->short_leave_free) * (float) $policy->short_leave_deduct;
        }

        $casualTaken = $sum($granted, 'Casual') + $deduct;
        $medicalTaken = $sum($granted, 'Medical');

        return [
            'policy' => $policy,
            'casual_taken' => $casualTaken,
            'medical_taken' => $medicalTaken,
            'deducted' => $deduct,
            'casual_left' => (float) $policy->casual_days - $casualTaken,
            'medical_left' => (float) $policy->medical_days - $medicalTaken,
            'short_this_month' => $shortByMonth[$yearMonth] ?? 0,
            'casual_pending' => $sum($pending, 'Casual'),
            'medical_pending' => $sum($pending, 'Medical'),
            'other_taken' => $sum($granted, 'Other'),
        ];
    }

    /**
     * Whether a delegate relationship is currently active: $delegate is
     * covering for $approver today.
     */
    public function isDelegateActive(Staff $approver, Staff $delegate): bool
    {
        return $approver->delegate_id === $delegate->id
            && $approver->delegate_until !== null
            && $approver->delegate_until->greaterThanOrEqualTo(today());
    }

    /**
     * Whether $user may approve/reject/clarify this application right now —
     * only the current step's approver (or their active delegate), or any
     * HR officer once the chain reaches the HR step.
     */
    public function canAct(Staff $user, LeaveApplication $leave): bool
    {
        if ($leave->status !== 'pending' || $leave->staff_id === $user->id) {
            return false;
        }

        $step = $leave->steps->firstWhere('step_order', $leave->current_step);
        if (! $step) {
            return false;
        }

        if ($step->kind === 'hr') {
            if ($user->role === 'hr') {
                return true;
            }
            $otherHr = Staff::where('role', 'hr')->where('id', '!=', $leave->staff_id)->exists();

            return ! $otherHr && $user->role === 'headmaster';
        }

        if ($step->approver_id === $user->id) {
            return true;
        }

        $approver = $step->approver_id ? Staff::find($step->approver_id) : null;

        return $approver && $this->isDelegateActive($approver, $user);
    }

    public function notify(?Staff $staff, string $title, string $message): void
    {
        if (! $staff) {
            return;
        }

        Notification::create(['staff_id' => $staff->id, 'title' => $title, 'message' => $message]);
    }

    /**
     * Notify whoever needs to act on the application's current step —
     * the approver (and their active delegate), or every HR officer once
     * it reaches HR verification.
     */
    public function notifyStepOwners(LeaveApplication $leave): void
    {
        $step = $leave->steps->firstWhere('step_order', $leave->current_step);
        if (! $step) {
            return;
        }

        $days = (float) $leave->days;
        $message = "{$leave->staff->name} — {$leave->type} leave, {$this->rangeText($leave)} ({$days} day".($days == 1 ? '' : 's').') needs your '.($step->kind === 'hr' ? 'verification' : 'approval').'.';

        if ($step->kind === 'hr') {
            $hrUsers = Staff::where('role', 'hr')->where('id', '!=', $leave->staff_id)->get();
            if ($hrUsers->isEmpty()) {
                $this->notify(Staff::where('role', 'headmaster')->first(), 'Leave waiting for verification', $message);
            } else {
                $hrUsers->each(fn (Staff $hr) => $this->notify($hr, 'Leave waiting for verification', $message));
            }

            return;
        }

        $approver = $step->approver_id ? Staff::find($step->approver_id) : null;
        $this->notify($approver, 'Leave waiting for approval', $message);

        if ($approver && $approver->delegate_id) {
            $delegate = Staff::find($approver->delegate_id);
            if ($delegate && $this->isDelegateActive($approver, $delegate)) {
                $this->notify($delegate, 'Leave waiting for approval (acting)', $message);
            }
        }
    }

    public function rangeText(LeaveApplication $leave): string
    {
        if ($leave->type === 'Short') {
            return $leave->from_date->format('j M').', '.$this->t12($leave->start_time).'–'.$this->t12($leave->end_time);
        }

        return $leave->from_date->isSameDay($leave->to_date)
            ? $leave->from_date->format('j M Y')
            : $leave->from_date->format('j M').' – '.$leave->to_date->format('j M Y');
    }

    public function t12(?string $hm): string
    {
        if (! $hm) {
            return '———';
        }

        return Carbon::createFromFormat('H:i', $hm)->format('g:i A');
    }
}
