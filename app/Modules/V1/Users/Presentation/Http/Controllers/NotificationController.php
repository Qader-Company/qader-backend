<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Notifications\Application\Support\NotificationTypes;
use App\Modules\V1\Users\Application\Services\NotificationInbox;
use App\Modules\V1\Users\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/** Talent and admin notifications. Client notifications are per workspace: ClientNotificationController. */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationInbox $inbox) {}

    #[QueryParam('unread_only', 'boolean', 'Return unread notifications only.', required: false, example: true)]
    #[QueryParam('per_page', 'integer', 'Items per page from 1 to 100.', required: false, example: 20)]
    #[Response('{"success":true,"data":{"notifications":[{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_received","title":"New job offer","body":"You received a job offer from Qader Technology for Backend Developer.","target_id":9,"read_at":null,"created_at":"2026-11-19T14:00:00+00:00"}],"meta":{"current_page":1,"last_page":1,"per_page":20,"total":1}}}')]
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->inbox->list($request, $request->user()->notifications()));
    }

    #[Response('{"success":true,"data":{"unread_count":3}}')]
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $this->inbox->unreadCount($request->user()->notifications())]);
    }

    #[Response('{"success":true,"data":{"types":[{"value":"hiring_offer_received","label":"New job offer"},{"value":"hiring_interview_confirmed","label":"Interview confirmed"},{"value":"talent_application_accepted","label":"Application accepted"}]}}')]
    public function types(Request $request): JsonResponse
    {
        // Shared by the talent and admin portals; the portal comes from the URL (/talent/... or /admin/...).
        $portal = str_contains((string) $request->route()?->uri(), '/admin/') ? 'admin' : 'talent';

        return ApiResponse::success(['types' => NotificationTypes::options($portal)]);
    }

    #[UrlParam('notification', 'string', 'Notification UUID.', example: '550e8400-e29b-41d4-a716-446655440000')]
    #[Response('{"success":true,"message":"Notification marked as read successfully.","data":{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_received","title":"New job offer","body":"You received a job offer from Qader Technology for Backend Developer.","target_id":9,"read_at":"2026-11-19T14:05:00+00:00","created_at":"2026-11-19T14:00:00+00:00"}}')]
    public function markAsRead(string $notification, Request $request): JsonResponse
    {
        return ApiResponse::updated(
            new NotificationResource($this->inbox->markAsRead($request->user()->notifications(), $notification)),
            __('notifications.marked_as_read'),
        );
    }

    #[Response('{"success":true,"message":"All notifications marked as read successfully.","data":{"unread_count":0}}')]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->inbox->markAllAsRead($request->user()->notifications());

        return ApiResponse::updated(['unread_count' => 0], __('notifications.all_marked_as_read'));
    }
}
