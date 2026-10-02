<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\SetFirstPasswordRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $staff = Staff::where('emp_id', $request->string('emp_id'))->first();

        if (! $staff || ! Auth::guard('web')->attempt(['emp_id' => $request->input('emp_id'), 'password' => $request->input('password')])) {
            throw ValidationException::withMessages([
                'emp_id' => "That employee ID and password don't match. Check both and try again.",
            ]);
        }

        if ($staff->status === 'Inactive') {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages([
                'emp_id' => 'This account is inactive. Contact HR.',
            ]);
        }

        $request->session()->regenerate();

        return StaffResource::make($staff->load('firstApprover', 'delegate'));
    }

    public function logout(Request $request)
    {
        AuditLogger::log($request->user(), "{$request->user()->name} signed out");

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return StaffResource::make($request->user()->load('firstApprover', 'delegate'));
    }

    public function setFirstPassword(SetFirstPasswordRequest $request)
    {
        $staff = $request->user();
        $staff->update([
            'password' => $request->string('password'),
            'must_change_password' => false,
        ]);

        AuditLogger::log($staff, "{$staff->name} set their password");

        return StaffResource::make($staff);
    }
}
