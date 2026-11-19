<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

class NotificationController extends Controller
{
    #[QueryParam('unread_only', 'boolean', 'Return unread notifications only.', required: false, example: true)]
    #[QueryParam('per_page', 'integer', 'Items per page from 1 to 100.', required: false, example: 20)]
    #[Response('{"success":true,"data":{"items":[{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_received","data":{"title":"New hiring offer","body":"You have received a new hiring offer.","screen":"hiring_offer_details","item_id":9},"read_at":null,"created_at":"2026-11-19T14:00:00+02:00"}],"meta":{"current_page":1,"last_page":1,"per_page":20,"total":1}}}')]
    public function index(Request $request): JsonResponse
    {
        $validated = validator($request->query(), [
            'unread_only' => ['nullable', Rule::in(['true', 'false', '1', '0', 1, 0, true, false])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();
        $perPage = (int) ($validated['per_page'] ?? 20);

        $notifications = $request->user()
            ->notifications()
            ->when($request->boolean('unread_only'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success([
            'items' => NotificationResource::collection($notifications->getCollection()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    #[Response('{"success":true,"data":{"unread_count":3}}')]
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    #[UrlParam('notification', 'string', 'Notification UUID.', example: '550e8400-e29b-41d4-a716-446655440000')]
    #[Response('{"success":true,"message":"Notification marked as read successfully.","data":{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_received","data":{"screen":"hiring_offer_details","item_id":9},"read_at":"2026-11-19T14:05:00+02:00","created_at":"2026-11-19T14:00:00+02:00"}}')]
    public function markAsRead(string $notification, Request $request): JsonResponse
    {
        $record = $this->notificationForUser($request, $notification);
        $record->markAsRead();

        return ApiResponse::updated(
            new NotificationResource($record->refresh()),
            __('notifications.marked_as_read'),
        );
    }

    #[Response('{"success":true,"message":"All notifications marked as read successfully.","data":{"unread_count":0}}')]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::updated(
            ['unread_count' => 0],
            __('notifications.all_marked_as_read'),
        );
    }

    private function notificationForUser(Request $request, string $id): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();
    }
}
