<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecideLeaveRequest;
use App\Http\Requests\HrCancelLeaveRequest;
use App\Http\Requests\ResubmitLeaveRequest;
use App\Http\Requests\StoreLeaveApplicationRequest;
use App\Http\Resources\LeaveApplicationResource;
use App\Http\Resources\StaffSummaryResource;
use App\Models\LeaveApplication;
use App\Models\Staff;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveApplicationController extends Controller
{
    protected array $withAll = ['staff', 'steps.approver', 'steps.actedBy', 'histories.byStaff'];

    public function index(Request $request, LeaveService $leaveService)
    {
        $user = $request->user();
        $scope = $request->query('scope', 'mine');
        $status = $request->query('status');

        if (in_array($scope, ['approvals', 'hr_verification'], true)) {
            if ($scope === 'hr_verification') {
                abort_unless(in_array($user->role, ['hr', 'headmaster'], true), 403);
            }

            $pending = LeaveApplication::with(['staff', 'steps'])
                ->where('status', 'pending')
                ->where('staff_id', '!=', $user->id)
                ->get();

            $ids = $pending->filter(function (LeaveApplication $leave) use ($user, $leaveService, $scope) {
                if (! $leaveService->canAct($user, $leave)) {
                    return false;
                }
                $kind = $leave->steps->firstWhere('step_order', $leave->current_step)?->kind;

                return $scope === 'hr_verification' ? $kind === 'hr' : $kind !== 'hr';
            })->pluck('id');

            $query = LeaveApplication::with($this->withAll)->whereIn('id', $ids);
        } else {
            $query = LeaveApplication::with($this->withAll);
            $staffId = $request->query('staff_id');
            if ($staffId && in_array($user->role, ['hr', 'headmaster'], true)) {
                $query->where('staff_id', $staffId);
            } else {
                $query->where('staff_id', $user->id);
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        return LeaveApplicationResource::collection($query->latest('created_at')->get());
    }

    public function store(StoreLeaveApplicationRequest $request, LeaveService $leaveService)
    {
        $user = $request->user();
        $type = $request->string('type')->value();
        $from = Carbon::parse($request->input('from_date'))->startOfDay();
        $to = $type === 'Short' ? $from->copy() : Carbon::parse($request->input('to_date'))->startOfDay();

        if ($from->lt(today()) && $type !== 'Medical') {
            throw ValidationException::withMessages([
                'from_date' => 'Only medical leave can be applied for past dates. Choose today or a later date.',
            ]);
        }

        $half = $request->boolean('half') && $from->isSameDay($to);
        $days = $type === 'Short' ? 1.0 : ($half ? 0.5 : (float) $leaveService->workDays($from, $to));

        if ($days <= 0) {
            throw ValidationException::withMessages([
                'from_date' => 'Those dates fall on weekends or school holidays. Choose working days.',
            ]);
        }

        $overlap = LeaveApplication::where('staff_id', $user->id)
            ->whereIn('status', ['pending', 'clarify', 'granted'])
            ->where('from_date', '<=', $to)
            ->where('to_date', '>=', $from)
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'from_date' => "You already have {$overlap->type} leave for {$leaveService->rangeText($overlap)}. Cancel that application first or choose other dates.",
            ]);
        }

        $balances = $leaveService->balances($user);
        if ($type === 'Casual' && $days > $balances['casual_left'] - $balances['casual_pending']) {
            throw ValidationException::withMessages(['type' => 'You do not have enough casual leave available after pending applications.']);
        }
        if ($type === 'Medical' && $days > $balances['medical_left'] - $balances['medical_pending']) {
            throw ValidationException::withMessages(['type' => 'You do not have enough medical leave available after pending applications.']);
        }

        $certificatePath = $request->hasFile('certificate')
            ? $request->file('certificate')->store('certificates', 'public')
            : null;

        $leave = LeaveApplication::create([
            'staff_id' => $user->id,
            'type' => $type,
            'from_date' => $from,
            'to_date' => $to,
            'days' => $days,
            'reason' => $request->string('reason'),
            'start_time' => $type === 'Short' ? $request->input('start_time') : null,
            'end_time' => $type === 'Short' ? $request->input('end_time') : null,
            'certificate_path' => $certificatePath,
            'status' => 'pending',
            'current_step' => 0,
        ]);

        foreach ($leaveService->buildSteps($user) as $order => $step) {
            $leave->steps()->create([
                'step_order' => $order,
                'kind' => $step['kind'],
                'label' => $step['label'],
                'approver_id' => $step['approver_id'],
            ]);
        }

        $leave->histories()->create(['by_staff_id' => $user->id, 'action' => 'Applied']);
        $leave->load($this->withAll);

        $leaveService->notifyStepOwners($leave);
        AuditLogger::log($user, "{$user->name} applied for {$type} leave #{$leave->id} ({$leaveService->rangeText($leave)}, {$days} day".($days == 1 ? '' : 's').')');

        return LeaveApplicationResource::make($leave)->response()->setStatusCode(201);
    }

    public function show(LeaveApplication $leaveApplication, Request $request)
    {
        $user = $request->user();
        $leaveApplication->load($this->withAll);

        $isApprover = $leaveApplication->steps->contains(fn ($s) => $s->approver_id === $user->id);
        $allowed = $leaveApplication->staff_id === $user->id || $isApprover || in_array($user->role, ['hr', 'headmaster'], true);
        abort_unless($allowed, 403);

        return LeaveApplicationResource::make($leaveApplication);
    }

    public function decide(DecideLeaveRequest $request, LeaveApplication $leaveApplication, LeaveService $leaveService)
    {
        $user = $request->user();
        $leaveApplication->load(['steps', 'staff']);

        abort_if($leaveApplication->status !== 'pending', 409, 'This application has already moved on.');
        abort_unless($leaveService->canAct($user, $leaveApplication), 403, 'You are not authorised to act on this application.');

        $action = $request->input('action');
        $comment = $request->input('comment');
        $step = $leaveApplication->steps->firstWhere('step_order', $leaveApplication->current_step);

        if ($action === 'approve') {
            $step->update(['status' => 'approved', 'acted_by' => $user->id, 'acted_at' => now(), 'comment' => $comment]);
            $label = $step->kind === 'hr' ? 'HR' : $step->label;

            if ($leaveApplication->current_step < $leaveApplication->steps->count() - 1) {
                $leaveApplication->increment('current_step');
                $leaveService->notify($leaveApplication->staff, 'Leave update', "Your {$leaveApplication->type} leave #{$leaveApplication->id} was approved by the {$label}.");
                $leaveService->notifyStepOwners($leaveApplication);
            } else {
                $leaveApplication->update(['status' => 'granted']);
                $leaveService->notify($leaveApplication->staff, 'Leave granted', "Your {$leaveApplication->type} leave from {$leaveService->rangeText($leaveApplication)} has been approved. Status: leave granted.");
            }

            AuditLogger::log($user, "{$user->name} ".($step->kind === 'hr' ? 'verified and granted' : 'approved')." leave #{$leaveApplication->id} ({$leaveApplication->staff->name})");
        } elseif ($action === 'reject') {
            $step->update(['status' => 'rejected', 'acted_by' => $user->id, 'acted_at' => now(), 'comment' => $comment]);
            $leaveApplication->update(['status' => 'rejected']);
            $leaveService->notify($leaveApplication->staff, 'Leave rejected', "Your leave #{$leaveApplication->id} was rejected by {$user->name}: {$comment}");
            AuditLogger::log($user, "{$user->name} rejected leave #{$leaveApplication->id} ({$leaveApplication->staff->name})");
        } else {
            $step->update(['comment' => $comment]);
            $leaveApplication->update(['status' => 'clarify']);
            $leaveApplication->histories()->create(['by_staff_id' => $user->id, 'action' => 'Clarification requested', 'comment' => $comment]);
            $leaveService->notify($leaveApplication->staff, 'Clarification requested', "{$user->name} asked about leave #{$leaveApplication->id}: {$comment}");
            AuditLogger::log($user, "{$user->name} requested clarification on leave #{$leaveApplication->id} ({$leaveApplication->staff->name})");
        }

        return LeaveApplicationResource::make($leaveApplication->fresh($this->withAll));
    }

    public function resubmit(ResubmitLeaveRequest $request, LeaveApplication $leaveApplication, LeaveService $leaveService)
    {
        $user = $request->user();
        abort_unless($leaveApplication->staff_id === $user->id && $leaveApplication->status === 'clarify', 403);

        $updates = ['status' => 'pending'];
        if ($request->filled('reason')) {
            $updates['reason'] = $request->string('reason');
        }
        if ($request->hasFile('certificate')) {
            $updates['certificate_path'] = $request->file('certificate')->store('certificates', 'public');
        }
        $leaveApplication->update($updates);

        $leaveApplication->load('steps');
        $leaveApplication->steps->firstWhere('step_order', $leaveApplication->current_step)?->update(['comment' => null]);

        $leaveApplication->histories()->create(['by_staff_id' => $user->id, 'action' => 'Resubmitted', 'comment' => $request->input('reason')]);
        $leaveService->notifyStepOwners($leaveApplication);
        AuditLogger::log($user, "{$user->name} resubmitted leave #{$leaveApplication->id}");

        return LeaveApplicationResource::make($leaveApplication->fresh($this->withAll));
    }

    public function cancel(LeaveApplication $leaveApplication, Request $request)
    {
        $user = $request->user();
        abort_unless($leaveApplication->staff_id === $user->id && in_array($leaveApplication->status, ['pending', 'clarify'], true), 403);

        $leaveApplication->update(['status' => 'cancelled']);
        $leaveApplication->histories()->create(['by_staff_id' => $user->id, 'action' => 'Cancelled by applicant']);
        AuditLogger::log($user, "{$user->name} cancelled leave #{$leaveApplication->id}");

        return LeaveApplicationResource::make($leaveApplication->fresh($this->withAll));
    }

    public function hrCancel(HrCancelLeaveRequest $request, LeaveApplication $leaveApplication, LeaveService $leaveService)
    {
        $user = $request->user();
        abort_unless($leaveApplication->status === 'granted', 409, 'Only a granted leave can be cancelled this way.');

        $comment = $request->string('comment');
        $leaveApplication->load('staff');
        $leaveApplication->update(['status' => 'cancelled']);
        $leaveApplication->histories()->create(['by_staff_id' => $user->id, 'action' => 'Granted leave cancelled by HR', 'comment' => $comment]);
        $leaveService->notify($leaveApplication->staff, 'Leave cancelled', "HR cancelled your granted leave #{$leaveApplication->id}: {$comment}. Your balance has been restored.");
        AuditLogger::log($user, "{$user->name} cancelled granted leave #{$leaveApplication->id} ({$leaveApplication->staff->name}): {$comment}");

        return LeaveApplicationResource::make($leaveApplication->fresh($this->withAll));
    }

    public function monthlyReport(Request $request, LeaveService $leaveService)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $year = (int) substr($month, 0, 4);
        $monthNum = (int) substr($month, 5, 2);

        $data = Staff::where('status', 'Active')->orderBy('name')->get()
            ->map(function (Staff $staff) use ($year, $monthNum, $month, $leaveService) {
                $granted = LeaveApplication::where('staff_id', $staff->id)
                    ->where('status', 'granted')
                    ->whereYear('from_date', $year)
                    ->whereMonth('from_date', $monthNum)
                    ->get();

                $sum = fn (string $type) => (float) $granted->where('type', $type)->sum('days');
                $balances = $leaveService->balances($staff, $year, $month);

                return [
                    'staff' => StaffSummaryResource::make($staff),
                    'casual' => $sum('Casual'),
                    'medical' => $sum('Medical'),
                    'short' => $granted->where('type', 'Short')->count(),
                    'other' => $sum('Other'),
                    'casual_left' => $balances['casual_left'],
                    'medical_left' => $balances['medical_left'],
                ];
            });

        return response()->json(['data' => $data]);
    }
}
