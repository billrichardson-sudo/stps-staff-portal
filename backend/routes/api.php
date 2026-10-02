<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceImportController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SetupController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\LeaveApplicationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes — no session yet
|--------------------------------------------------------------------------
*/
Route::get('/setup/status', [SetupController::class, 'status']);
Route::post('/setup/first-hr', [SetupController::class, 'firstHr']);
Route::post('/setup/demo', [SetupController::class, 'demo']);

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated routes — any signed-in staff member
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/first-password', [AuthController::class, 'setFirstPassword']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::put('/me/password', [ProfileController::class, 'updatePassword']);
    Route::get('/me/balance', [ProfileController::class, 'balance']);
    Route::get('/me/approval-chain', [ProfileController::class, 'approvalChain']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

    // Leave applications — visibility/authorization (own vs approver vs HR) is
    // enforced in the controller via the `scope` query param, not route middleware,
    // since who counts as "an approver" depends on the individual application.
    Route::get('/leave-applications/monthly-report', [LeaveApplicationController::class, 'monthlyReport'])
        ->middleware('role:headmaster');
    Route::get('/leave-applications', [LeaveApplicationController::class, 'index']);
    Route::post('/leave-applications', [LeaveApplicationController::class, 'store']);
    Route::get('/leave-applications/{leaveApplication}', [LeaveApplicationController::class, 'show']);
    Route::post('/leave-applications/{leaveApplication}/decide', [LeaveApplicationController::class, 'decide']);
    Route::post('/leave-applications/{leaveApplication}/resubmit', [LeaveApplicationController::class, 'resubmit']);
    Route::post('/leave-applications/{leaveApplication}/cancel', [LeaveApplicationController::class, 'cancel']);
    Route::post('/leave-applications/{leaveApplication}/hr-cancel', [LeaveApplicationController::class, 'hrCancel'])
        ->middleware('role:hr');

    // Attendance — everyone can see their own via index(); the rest are HR/headmaster.
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/daily', [AttendanceController::class, 'daily'])
        ->middleware('role:headmaster');
    Route::put('/attendance/correct', [AttendanceController::class, 'correct'])
        ->middleware('role:hr');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])
        ->middleware('role:hr');

    Route::get('/attendance/imports', [AttendanceImportController::class, 'index'])
        ->middleware('role:hr');
    Route::post('/attendance/imports', [AttendanceImportController::class, 'store'])
        ->middleware('role:hr');

    /*
    |--------------------------------------------------------------------------
    | HR / Headmaster only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:headmaster')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });

    Route::middleware('role:hr')->group(function () {
        Route::get('/staff', [StaffController::class, 'index']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::get('/staff/{staff}', [StaffController::class, 'show']);
        Route::put('/staff/{staff}', [StaffController::class, 'update']);
        Route::post('/staff/bulk', [StaffController::class, 'bulkUpdate']);

        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/holidays', [HolidayController::class, 'store']);
        Route::delete('/settings/holidays/{holiday}', [HolidayController::class, 'destroy']);
    });
});
