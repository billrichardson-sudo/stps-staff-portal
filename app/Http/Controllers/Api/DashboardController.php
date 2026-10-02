<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffSummaryResource;
use App\Models\LeaveApplication;
use App\Models\Staff;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function show(Request $request, AttendanceService $attendanceService, LeaveService $leaveService)
    {
        $date = Carbon::parse($request->query('date', $attendanceService->latestUpload() ?? today()->toDateString()));
        $staffList = Staff::where('status', 'Active')->get();

        $present = 0;
        $late = 0;
        $absent = 0;
        foreach ($staffList as $staff) {
            [$status] = $attendanceService->dayStatus($staff, $date);
            if (str_starts_with($status, 'Present') || $status === 'Late' || $status === 'Half Day') {
                $present++;
            }
            if ($status === 'Late') {
                $late++;
            }
            if ($status === 'Absent') {
                $absent++;
            }
        }

        $today = today();
        $onLeaveToday = $staffList->map(function (Staff $staff) use ($attendanceService, $today) {
            [$status, $color] = $attendanceService->dayStatus($staff, $today);

            return $color === 'info' ? ['staff' => StaffSummaryResource::make($staff), 'status' => $status] : null;
        })->filter()->values();

        $pendingApps = LeaveApplication::with('steps')->where('status', 'pending')->get();
        $pendingHr = $pendingApps->filter(fn ($l) => $l->steps->firstWhere('step_order', $l->current_step)?->kind === 'hr')->count();

        $byCategory = collect(['Tutorial', 'Support', 'Admin'])->map(function (string $category) use ($staffList, $leaveService) {
            $inCategory = $staffList->where('category', $category);
            $casual = 0;
            $medical = 0;
            $short = 0;
            foreach ($inCategory as $staff) {
                $balances = $leaveService->balances($staff);
                $casual += $balances['casual_taken'];
                $medical += $balances['medical_taken'];
                $short += $balances['short_this_month'];
            }

            return [
                'category' => $category,
                'staff_count' => $inCategory->count(),
                'casual_taken' => $casual,
                'medical_taken' => $medical,
                'short_this_month' => $short,
            ];
        });

        return response()->json(['data' => [
            'date' => $date->toDateString(),
            'total_staff' => $staffList->count(),
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'on_leave_today' => $onLeaveToday,
            'pending_hr_verification' => $pendingHr,
            'pending_all' => $pendingApps->count(),
            'missing_uploads' => $attendanceService->missingUploads(7),
            'by_category' => $byCategory,
        ]]);
    }
}
