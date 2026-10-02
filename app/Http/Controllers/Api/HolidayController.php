<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function store(StoreHolidayRequest $request)
    {
        $holiday = Holiday::create($request->validated());

        AuditLogger::log($request->user(), "{$request->user()->name} added holiday {$holiday->date->format('j M Y')} — {$holiday->name}");

        return HolidayResource::make($holiday)->response()->setStatusCode(201);
    }

    public function destroy(Holiday $holiday, Request $request)
    {
        AuditLogger::log($request->user(), "{$request->user()->name} removed holiday {$holiday->date->format('j M Y')}");
        $holiday->delete();

        return response()->noContent();
    }
}
