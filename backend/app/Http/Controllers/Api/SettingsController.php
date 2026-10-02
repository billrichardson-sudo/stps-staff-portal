<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\HolidayResource;
use App\Http\Resources\LeavePolicyResource;
use App\Http\Resources\SettingsResource;
use App\Models\AppSetting;
use App\Models\Holiday;
use App\Models\LeavePolicy;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function show()
    {
        return response()->json(['data' => $this->payload()]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        DB::transaction(function () use ($request) {
            foreach ($request->input('policies') as $category => $policy) {
                LeavePolicy::where('category', $category)->update([
                    'casual_days' => $policy['casual_days'],
                    'medical_days' => $policy['medical_days'],
                    'short_leave_free' => $policy['short_leave_free'],
                    'short_leave_deduct' => $policy['short_leave_deduct'],
                    'requires_first_approver' => $policy['requires_first_approver'],
                    'requires_headmaster' => $policy['requires_headmaster'],
                ]);
            }

            AppSetting::current()->update([
                'headmaster_approver_id' => $request->input('headmaster_approver_id'),
                'late_tutorial' => $request->input('late_tutorial'),
                'late_support' => $request->input('late_support'),
                'late_admin' => $request->input('late_admin'),
                'half_day_before' => $request->input('half_day_before'),
            ]);
        });

        AuditLogger::log($request->user(), "{$request->user()->name} updated leave policies and approval routes");

        return response()->json(['data' => $this->payload()]);
    }

    protected function payload(): array
    {
        return [
            'policies' => LeavePolicy::all()->keyBy('category')->map(fn ($p) => LeavePolicyResource::make($p)),
            'app_settings' => SettingsResource::make(AppSetting::current()->load('headmasterApprover')),
            'holidays' => HolidayResource::collection(Holiday::orderBy('date')->get()),
        ];
    }
}
