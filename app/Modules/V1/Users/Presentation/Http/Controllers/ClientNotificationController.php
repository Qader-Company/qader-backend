<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\Notifications\Application\Support\NotificationTypes;
use App\Modules\V1\Users\Application\Services\NotificationInbox;
use App\Modules\V1\Users\Presentation\Http\Resources\NotificationResource;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/** Client notifications, scoped to one workspace the client is an active member of. */
#[UrlParam('workspace', 'integer', 'Workspace ID.', example: 5)]
class ClientNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationInbox $inbox,
        private readonly WorkspaceAccessService $access,
    ) {}

    #[QueryParam('unread_only', 'boolean', 'Return unread notifications only.', required: false, example: true)]
    #[QueryParam('per_page', 'integer', 'Items per page from 1 to 100.', required: false, example: 20)]
    #[Response('{"success":true,"data":{"notifications":[{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_accepted","title":"Offer accepted","body":"Talent QT-1001 accepted the offer for Backend Developer (RI-2026-ZHPPYLQH).","target_id":4,"workspace_id":5,"params":{"reference":"RI-2026-ZHPPYLQH","company":"Qader Technology","job_title":"Backend Developer","talent_code":"QT-1001"},"read_at":null,"created_at":"2026-11-19T14:00:00+00:00"}],"meta":{"current_page":1,"last_page":1,"per_page":20,"total":1}}}')]
    public function index(Request $request, int $workspace): JsonResponse
    {
        return ApiResponse::success($this->inbox->list($request, $this->notifications($request, $workspace)));
    }

    #[Response('{"success":true,"data":{"unread_count":3}}')]
    public function unreadCount(Request $request, int $workspace): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $this->inbox->unreadCount($this->notifications($request, $workspace))]);
    }

    #[Response('{"success":true,"data":{"types":[{"value":"hiring_offer_accepted","label":"Offer accepted"},{"value":"help_request_closed","label":"Help request closed"},{"value":"record_created","label":"New record added"}]}}')]
    public function types(Request $request, int $workspace): JsonResponse
    {
        $this->notifications($request, $workspace); // Same membership check as the list.

        return ApiResponse::success(['types' => NotificationTypes::options('client')]);
    }

    #[UrlParam('notification', 'string', 'Notification UUID.', example: '550e8400-e29b-41d4-a716-446655440000')]
    #[Response('{"success":true,"message":"Notification marked as read successfully.","data":{"id":"550e8400-e29b-41d4-a716-446655440000","type":"hiring_offer_accepted","title":"Offer accepted","body":"Talent QT-1001 accepted the offer for Backend Developer (RI-2026-ZHPPYLQH).","target_id":4,"workspace_id":5,"params":{"reference":"RI-2026-ZHPPYLQH","company":"Qader Technology","job_title":"Backend Developer","talent_code":"QT-1001"},"read_at":"2026-11-19T14:05:00+00:00","created_at":"2026-11-19T14:00:00+00:00"}}')]
    public function markAsRead(Request $request, int $workspace, string $notification): JsonResponse
    {
        return ApiResponse::updated(
            new NotificationResource($this->inbox->markAsRead($this->notifications($request, $workspace), $notification)),
            __('notifications.marked_as_read'),
        );
    }

    #[Response('{"success":true,"message":"All notifications marked as read successfully.","data":{"unread_count":0}}')]
    public function markAllAsRead(Request $request, int $workspace): JsonResponse
    {
        $this->inbox->markAllAsRead($this->notifications($request, $workspace));

        return ApiResponse::updated(['unread_count' => 0], __('notifications.all_marked_as_read'));
    }

    /** Every client notification stores the workspace it belongs to (`data.workspace_id`). */
    private function notifications(Request $request, int $workspaceId): MorphMany
    {
        $this->access->member($request->user(), Workspace::query()->findOrFail($workspaceId));

        return $request->user()->notifications()->where('data->workspace_id', $workspaceId);
    }
}
