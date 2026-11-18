<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Application\Services\AdminGlobalSearchService;
use App\Modules\V1\AdminDashboard\Presentation\Http\Requests\AdminGlobalSearchRequest;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Response;

/**
 * @group Admin
 *
 * @subgroup Search
 */
class AdminGlobalSearchController extends Controller
{
    #[Response('{"success":true,"data":{"query":"Ahmed","requests":[{"type":"hiring_request","id":20,"reference":"HR-2026-MYY6WXEB","title":"HR-2026-MYY6WXEB","subtitle":"Qader Technology","created_at":"2026-11-18T08:00:00+02:00"},{"type":"talent_application","id":41,"reference":null,"title":"Ahmed Hassan","subtitle":"QDR-2847","created_at":"2026-11-17T08:00:00+02:00"}],"talents":[{"id":22,"talent_code":"QDR-2847","full_name":"Ahmed Hassan","job_title":"Backend Developer"}],"clients":[{"id":5,"full_name":"Ahmed Ali","email":"ahmed@example.com","workspaces":[{"id":4,"name":"Qader Technology"}]}]}}', 200, 'Grouped admin search results')]
    public function __invoke(AdminGlobalSearchRequest $request, AdminGlobalSearchService $service): JsonResponse
    {
        return ApiResponse::success($service->search(
            $request->validated('q'),
            (int) ($request->validated('limit') ?? 5),
        ));
    }
}
