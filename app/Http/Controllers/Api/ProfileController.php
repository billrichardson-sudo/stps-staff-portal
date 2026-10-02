<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request)
    {
        $staff = $request->user();
        $staff->update([
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'delegate_id' => $request->input('delegate_id'),
            'delegate_until' => $request->input('delegate_until'),
        ]);

        $suffix = $staff->delegate_id
            ? " (acting approver: {$staff->delegate?->name} until {$staff->delegate_until?->format('j M Y')})"
            : '';
        AuditLogger::log($staff, "{$staff->name} updated their profile{$suffix}");

        return StaffResource::make($staff->load('firstApprover', 'delegate'));
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $staff = $request->user();

        if (! Hash::check($request->string('old_password'), $staff->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $staff->update(['password' => $request->string('password')]);
        AuditLogger::log($staff, "{$staff->name} changed their password");

        return response()->json(['message' => 'Password changed']);
    }

    /**
     * GET /api/me/balance?year=&month=YYYY-MM
     * The signed-in staff member's own leave balance for the given year.
     */
    public function balance(Request $request, LeaveService $leaveService)
    {
        $year = $request->integer('year') ?: null;
        $month = $request->string('month')->value() ?: null;
        $balances = $leaveService->balances($request->user(), $year, $month);

        return response()->json(['data' => [
            'policy' => [
                'category' => $balances['policy']->category,
                'casual_days' => (float) $balances['policy']->casual_days,
                'medical_days' => (float) $balances['policy']->medical_days,
                'short_leave_free' => $balances['policy']->short_leave_free,
                'short_leave_deduct' => (float) $balances['policy']->short_leave_deduct,
            ],
            'casual_left' => $balances['casual_left'],
            'medical_left' => $balances['medical_left'],
            'casual_taken' => $balances['casual_taken'],
            'medical_taken' => $balances['medical_taken'],
            'casual_pending' => $balances['casual_pending'],
            'medical_pending' => $balances['medical_pending'],
            'short_this_month' => $balances['short_this_month'],
            'deducted' => $balances['deducted'],
        ]]);
    }

    /**
     * GET /api/me/approval-chain
     * Preview of the approval chain the signed-in staff member's next leave
     * application would follow — same routing used when actually applying.
     */
    public function approvalChain(Request $request, LeaveService $leaveService)
    {
        $steps = collect($leaveService->buildSteps($request->user()))->map(fn ($step) => [
            'kind' => $step['kind'],
            'label' => $step['label'],
            'approver_name' => $step['approver_id'] ? Staff::find($step['approver_id'])?->name : null,
        ]);

        return response()->json(['data' => $steps]);
    }
}
