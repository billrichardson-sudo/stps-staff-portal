<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceImportRequest;
use App\Http\Resources\AttendanceImportResource;
use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Services\AbsenceReminderService;
use App\Services\AuditLogger;

class AttendanceImportController extends Controller
{
    public function index()
    {
        return AttendanceImportResource::collection(
            AttendanceImport::with('importedBy')->latest('created_at')->get()
        );
    }

    public function store(StoreAttendanceImportRequest $request, AbsenceReminderService $absenceReminderService)
    {
        $user = $request->user();
        $records = collect($request->input('records'));

        $manualKeys = Attendance::where('source', 'manual')
            ->get(['staff_id', 'date'])
            ->map(fn ($a) => "{$a->staff_id}_{$a->date->toDateString()}")
            ->flip();

        $written = 0;
        foreach ($records as $record) {
            $key = "{$record['staff_id']}_{$record['date']}";
            if ($manualKeys->has($key)) {
                continue;
            }

            Attendance::updateOrCreate(
                ['staff_id' => $record['staff_id'], 'date' => $record['date']],
                [
                    'check_in' => $record['check_in'] ?? null,
                    'check_out' => $record['check_out'] ?? null,
                    'late' => $record['late'] ?? null,
                    'source' => 'fingerprint',
                ]
            );
            $written++;
        }

        $dates = $records->pluck('date')->unique()->sort()->values()->all();

        $import = AttendanceImport::create([
            'file_name' => $request->string('file_name'),
            'imported_by' => $user->id,
            'dates' => $dates,
            'records_count' => $written,
            'unmatched' => $request->input('unmatched', []),
        ]);

        $dayWord = count($dates) === 1 ? 'day' : 'days';
        AuditLogger::log($user, "{$user->name} uploaded fingerprint attendance \"{$import->file_name}\" — {$written} records for ".count($dates)." {$dayWord}");

        $absenceReminderService->checkNewAbsences($dates);
        $absenceReminderService->flagOverdueAbsences();

        return AttendanceImportResource::make($import->load('importedBy'))->response()->setStatusCode(201);
    }
}
