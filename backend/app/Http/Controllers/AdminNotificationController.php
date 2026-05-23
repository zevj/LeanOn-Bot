<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * GET /api/admin/notifications
     *
     * Returns the 30 most recent admin notifications, newest first.
     * Includes unread count for the bell badge.
     */
    public function index()
    {
        $notifications = AdminNotification::orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'title'   => $n->title,
                'message' => $n->message,
                'detail'  => $n->detail,
                'icon'    => $n->icon,
                'color'   => $n->color,
                'is_read' => $n->is_read,
                'meta'    => $n->meta,
                'time'    => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => AdminNotification::where('is_read', false)->count(),
        ]);
    }

    /**
     * PATCH /api/admin/notifications/{id}/read
     *
     * Mark a single notification as read.
     */
    public function markRead(int $id)
    {
        AdminNotification::where('id', $id)->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/admin/notifications/mark-all-read
     *
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }
}
