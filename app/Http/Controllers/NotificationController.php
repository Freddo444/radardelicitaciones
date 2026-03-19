<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Unread count for badge polling.
     */
    public function unreadCount(): JsonResponse
    {
        $count = InAppNotification::forUser(auth()->id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Recent notifications for dropdown.
     */
    public function recent(): JsonResponse
    {
        $notifications = InAppNotification::forUser(auth()->id())
            ->with('bid:id,process_code,secp_url')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'bid_id' => $n->bid_id,
                'process_code' => $n->bid?->process_code,
                'read' => $n->read_at !== null,
                'ago' => $n->created_at->diffForHumans(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Mark all as read.
     */
    public function markAllRead(): JsonResponse
    {
        InAppNotification::forUser(auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Mark one as read.
     */
    public function markRead(InAppNotification $inAppNotification): JsonResponse
    {
        if ($inAppNotification->user_id !== auth()->id()) {
            abort(403);
        }

        $inAppNotification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
