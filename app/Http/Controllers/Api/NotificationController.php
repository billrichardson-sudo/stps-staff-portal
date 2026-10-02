<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('staff_id', $request->user()->id)
            ->latest('created_at')
            ->get();

        return NotificationResource::collection($notifications)
            ->additional(['unread_count' => $notifications->whereNull('read_at')->count()]);
    }

    public function readAll(Request $request)
    {
        Notification::where('staff_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read']);
    }
}
