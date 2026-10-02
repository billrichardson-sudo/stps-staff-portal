<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\StaffSummaryResource;
use App\Models\Attendance;
use App\Models\Staff;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendanceService)
    {
        $user = $request->user();
        $staffId = $request->query('staff_id');
        $staff = $staffId && in_array($user->role, ['hr', 'headmaster'], true)
            ? Staff::findOrFail($staffId)
            : $user;

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth();
        $end = $start->copy()->endOfMonth()->min(today());

        $records = Attendance::where('staff_id', $staff->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $data = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $record = $records->get($date->toDateString());
            [$status, $color] = $attendanceService->dayStatus($staff, $date);
            $data[] = [
                'date' => $date->toDateString(),
                'check_in' => $record?->check_in,
                'check_out' => $record?->check_out,
                'source' => $record?->source,
                'status' => $status,
                'status_color' => $color,
            ];
        }

        return response()->json(['data' => array_reverse($data)]);
    }

    public function daily(Request $request, AttendanceService $attendanceService)
    {
        $date = Carbon::parse($request->query('date', $attendanceService->latestUpload() ?? today()->toDateString()));
        $dateStr = $date->toDateString();

        $staffList = Staff::where('status', 'Active')->orderBy('name')->get();
        $records = Attendance::where('date', $dateStr)->get()->keyBy('staff_id');

        $present = 0;
        $late = 0;
        $absent = 0;
        $onLeave = 0;

        $rows = $staffList->map(function (Staff $staff) use ($records, $date, $attendanceService, &$present, &$late, &$absent, &$onLeave) {
            $record = $records->get($staff->id);
            [$status, $color] = $attendanceService->dayStatus($staff, $date);

            if (str_starts_with($status, 'Present') || $status === 'Late' || $status === 'Half Day') {
                $present++;
            }
            if ($status === 'Late') {
                $late++;
            }
            if ($status === 'Absent') {
                $absent++;
            }
            if ($color === 'info') {
                $onLeave++;
            }

            return [
                'staff' => StaffSummaryResource::make($staff),
                'check_in' => $record?->check_in,
                'check_out' => $record?->check_out,
                'status' => $status,
                'status_color' => $color,
                'source' => $record?->source,
            ];
        });

        return response()->json([
            'data' => $rows,
            'summary' => ['present' => $present, 'late' => $late, 'on_leave' => $onLeave, 'absent' => $absent],
        ]);
    }

    public function correct(CorrectAttendanceRequest $request)
    {
        $user = $request->user();
        $staff = Staff::findOrFail($request->input('staff_id'));
        $date = $request->input('date');

        $attendance = Attendance::updateOrCreate(
            ['staff_id' => $staff->id, 'date' => $date],
            [
                'check_in' => $request->input('check_in'),
                'check_out' => $request->input('check_out'),
                'source' => 'manual',
                'reason' => $request->string('reason'),
                'corrected_by' => $user->id,
            ]
        );

        AuditLogger::log($user, "{$user->name} corrected attendance for {$staff->name} on ".Carbon::parse($date)->format('j M Y').": {$request->input('reason')}");

        return AttendanceResource::make($attendance->load('staff', 'correctedBy'));
    }

    public function destroy(Attendance $attendance, Request $request)
    {
        $user = $request->user();
        $attendance->load('staff');
        AuditLogger::log($user, "{$user->name} removed the attendance record for {$attendance->staff->name} on {$attendance->date->format('j M Y')}");
        $attendance->delete();

        return response()->noContent();
    }
}
