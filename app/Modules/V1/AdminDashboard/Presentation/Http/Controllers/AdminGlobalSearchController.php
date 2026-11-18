<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Application\Services\AdminGlobalSearchService;
use App\Modules\V1\AdminDashboard\Presentation\Http\Requests\AdminGlobalSearchRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin
 *
 * @subgroup Search
 */
class AdminGlobalSearchController extends Controller
{
    public function __invoke(AdminGlobalSearchRequest $request, AdminGlobalSearchService $service): JsonResponse
    {
        return ApiResponse::success($service->search(
            $request->validated('q'),
            (int) ($request->validated('limit') ?? 5),
        ));
    }
}
