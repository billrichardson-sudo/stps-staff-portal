<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FirstHrRequest;
use App\Http\Resources\StaffResource;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalStep;
use App\Models\Staff;
use App\Services\AuditLogger;
use App\Services\LeaveService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SetupController extends Controller
{
    public function status()
    {
        return response()->json(['needs_setup' => ! Staff::exists()]);
    }

    public function firstHr(FirstHrRequest $request)
    {
        abort_if(Staff::exists(), 409, 'Staff already exist.');

        $empId = $request->string('emp_id');
        $staff = Staff::create([
            'emp_id' => $empId,
            'name' => $request->string('name'),
            'designation' => 'HR Officer',
            'category' => 'Admin',
            'department' => 'Administration',
            'role' => 'hr',
            'status' => 'Active',
            'password' => $request->string('password'),
        ]);

        Auth::guard('web')->login($staff);
        $request->session()->regenerate();

        AuditLogger::log(null, 'Portal set up; first HR account created');

        return StaffResource::make($staff);
    }

    public function demo(LeaveService $leaveService)
    {
        abort_if(Staff::exists(), 409, 'Staff already exist.');

        $make = fn (array $attrs) => Staff::create(array_merge([
            'status' => 'Active',
            'password' => '1234',
        ], $attrs));

        $hr = $make(['emp_id' => 'E001', 'bio_id' => '9001', 'name' => 'Nirmala Fernando', 'designation' => 'HR Manager', 'category' => 'Admin', 'department' => 'Administration', 'role' => 'hr']);
        $hm = $make(['emp_id' => 'E002', 'bio_id' => '9002', 'name' => 'Rohan Jayasuriya', 'designation' => 'Headmaster', 'category' => 'Admin', 'department' => 'Administration', 'role' => 'headmaster']);
        $sh1 = $make(['emp_id' => 'E003', 'bio_id' => '9003', 'name' => 'Dilani Perera', 'designation' => "Sectional Head — Primary", 'category' => 'Tutorial', 'department' => 'Primary', 'role' => 'sectional_head']);
        $sh2 = $make(['emp_id' => 'E004', 'bio_id' => '9004', 'name' => 'Asanka Silva', 'designation' => 'Sectional Head — Middle School', 'category' => 'Tutorial', 'department' => 'Middle School', 'role' => 'sectional_head']);
        $t1 = $make(['emp_id' => 'E010', 'bio_id' => '9010', 'name' => 'John Silva', 'designation' => 'Class Teacher', 'category' => 'Tutorial', 'department' => 'Primary', 'role' => 'staff', 'first_approver_id' => $sh1->id]);
        $t2 = $make(['emp_id' => 'E011', 'bio_id' => '9011', 'name' => 'Anushka Wijesinghe', 'designation' => 'English Teacher', 'category' => 'Tutorial', 'department' => 'Primary', 'role' => 'staff', 'first_approver_id' => $sh1->id]);
        $t3 = $make(['emp_id' => 'E012', 'bio_id' => '9012', 'name' => 'Ravi Kumar', 'designation' => 'Mathematics Teacher', 'category' => 'Tutorial', 'department' => 'Middle School', 'role' => 'staff', 'first_approver_id' => $sh2->id]);
        $sup = $make(['emp_id' => 'E020', 'bio_id' => '9020', 'name' => 'Sunil Bandara', 'designation' => 'Support Staff Supervisor', 'category' => 'Support', 'department' => 'Estate', 'role' => 'supervisor']);
        $s1 = $make(['emp_id' => 'E021', 'bio_id' => '9021', 'name' => 'David Perera', 'designation' => 'Lab Assistant', 'category' => 'Support', 'department' => 'Science', 'role' => 'staff', 'first_approver_id' => $sup->id]);
        $s2 = $make(['emp_id' => 'E022', 'bio_id' => '9022', 'name' => 'Kamala Herath', 'designation' => 'Library Assistant', 'category' => 'Support', 'department' => 'Library', 'role' => 'staff', 'first_approver_id' => $sup->id]);
        $a1 = $make(['emp_id' => 'E030', 'bio_id' => '9030', 'name' => 'Priya Ratnayake', 'designation' => 'Admin Officer', 'category' => 'Admin', 'department' => 'Office', 'role' => 'staff']);

        $all = [$hr, $hm, $sh1, $sh2, $t1, $t2, $t3, $sup, $s1, $s2, $a1];

        $days = [];
        $cursor = today();
        while (count($days) < 5) {
            $cursor = $cursor->copy()->subDay();
            if (! $cursor->isWeekend()) {
                $days[] = $cursor->copy();
            }
        }

        foreach ($days as $i => $day) {
            foreach ($all as $j => $staff) {
                if ($staff->id === $t2->id && $i === 1) {
                    continue;
                }
                $late = ($i + $j) % 7 === 0;
                $in = $late ? sprintf('07:%02d', 40 + (($i + $j) % 15)) : sprintf('07:%02d', 5 + (($i * 3 + $j * 7) % 24));
                $out = sprintf('13:%02d', 30 + (($i + $j) % 25));
                Attendance::create(['staff_id' => $staff->id, 'date' => $day->toDateString(), 'check_in' => $in, 'check_out' => $out, 'source' => 'fingerprint']);
            }
        }

        AttendanceImport::create([
            'file_name' => 'biostar-demo.csv',
            'imported_by' => $hr->id,
            'dates' => collect($days)->map->toDateString()->sort()->values()->all(),
            'records_count' => count($days) * count($all) - 1,
            'unmatched' => [],
        ]);

        $future = function (int $n) {
            $date = today()->copy()->addDays($n);
            while ($date->isWeekend()) {
                $date->addDay();
            }

            return $date;
        };

        $makeLeave = function (Staff $staff, string $type, $from, $to, string $reason, int $stage, string $status) use ($leaveService, $hr) {
            $steps = $leaveService->buildSteps($staff);
            $createdAt = now()->subDays(3 - $stage);
            $days = $type === 'Short' ? 1 : $leaveService->workDays($from, $to);

            $leave = LeaveApplication::create([
                'staff_id' => $staff->id,
                'type' => $type,
                'from_date' => $from,
                'to_date' => $to,
                'days' => $days,
                'reason' => $reason,
                'status' => $status,
                'current_step' => min($stage, count($steps) - 1),
                'created_at' => $createdAt,
            ]);

            foreach ($steps as $order => $step) {
                $leave->steps()->create([
                    'step_order' => $order,
                    'kind' => $step['kind'],
                    'label' => $step['label'],
                    'approver_id' => $step['approver_id'],
                    'status' => $order < $stage ? 'approved' : 'waiting',
                    'acted_by' => $order < $stage ? ($step['kind'] === 'hr' ? $hr->id : $step['approver_id']) : null,
                    'acted_at' => $order < $stage ? $createdAt : null,
                ]);
            }

            $leave->histories()->create(['by_staff_id' => $staff->id, 'action' => 'Applied', 'created_at' => $createdAt]);

            return $leave;
        };

        $makeLeave($t1, 'Casual', $future(5), $future(7), 'Family function in Kandy', 0, 'pending');
        $makeLeave($s1, 'Medical', $days[0], $days[0], 'Fever — certificate to follow', 2, 'pending');
        $makeLeave($t2, 'Casual', $days[1], $days[1], 'Personal matter', 3, 'granted');

        AuditLogger::log(null, 'Demo staff and sample records loaded');

        return response()->json(['message' => 'Demo staff loaded. Sign in with E010 / 1234 to start as a teacher.']);
    }
}
