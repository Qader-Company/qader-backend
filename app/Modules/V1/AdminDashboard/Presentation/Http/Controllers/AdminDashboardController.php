<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Application\Services\AdminDashboardService;
use App\Modules\V1\AdminDashboard\Presentation\Http\Requests\GetAdminDashboardRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin
 *
 * @subgroup Dashboard
 */
class AdminDashboardController extends Controller
{
    public function __invoke(GetAdminDashboardRequest $request, AdminDashboardService $service): JsonResponse
    {
        return ApiResponse::success($service->get($request->period()));
    }
}
