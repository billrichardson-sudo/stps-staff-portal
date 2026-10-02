<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdateStaffRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::with('firstApprover')->orderBy('name');

        if (! $request->boolean('all')) {
            $query->where('status', 'Active');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('emp_id', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        return StaffResource::collection($query->get());
    }

    public function store(StoreStaffRequest $request)
    {
        $staff = Staff::create($request->validated());

        AuditLogger::log($request->user(), "{$request->user()->name} added staff member {$staff->name} ({$staff->emp_id})");

        return StaffResource::make($staff->load('firstApprover'))->response()->setStatusCode(201);
    }

    public function show(Staff $staff)
    {
        return StaffResource::make($staff->load('firstApprover', 'delegate'));
    }

    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        if ($request->input('role') === 'headmaster') {
            $other = Staff::where('role', 'headmaster')->where('id', '!=', $staff->id)->first();
            if ($other) {
                throw ValidationException::withMessages(['role' => "{$other->name} is already the Headmaster. Change their role first."]);
            }
        }

        $data = $request->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = $request->string('password');
            $data['must_change_password'] = true;
        }
        $staff->update($data);

        AuditLogger::log($request->user(), "{$request->user()->name} updated staff member {$staff->name} ({$staff->emp_id})");

        return StaffResource::make($staff->load('firstApprover'));
    }

    public function bulkUpdate(BulkUpdateStaffRequest $request)
    {
        $ids = $request->input('staff_ids');
        $updates = array_filter([
            'first_approver_id' => $request->input('first_approver_id'),
            'category' => $request->input('category'),
        ], fn ($v) => $v !== null);

        abort_if(empty($updates), 422, 'Nothing to update.');

        $updated = Staff::whereIn('id', $ids)->update($updates);

        AuditLogger::log($request->user(), "{$request->user()->name} bulk-updated {$updated} staff member".($updated == 1 ? '' : 's'));

        return response()->json(['message' => 'Updated', 'updated' => $updated]);
    }
}
