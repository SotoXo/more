<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = $this->limit($request, 50);
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn ($notification) => $this->formatNotification($notification))->values(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $limit = $this->limit($request, 5);
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'latest_notifications' => $notifications->map(fn ($notification) => $this->formatNotification($notification))->values(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'notification' => $this->formatNotification($notification->fresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    private function formatNotification($notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'type' => $data['type'] ?? null,
            'url' => $data['url'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'icon' => $data['icon'] ?? null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
        ];
    }

    private function limit(Request $request, int $default): int
    {
        return min(100, max(1, $request->integer('limit', $default)));
    }
}
