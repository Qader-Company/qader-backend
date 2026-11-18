<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Domain\ValueObjects\DashboardPeriodEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Response;

/**
 * @group Admin
 *
 * @subgroup Dashboard
 */
class AdminDashboardOptionsController extends Controller
{
    #[Response('{"success":true,"data":{"periods":[{"value":"day","label":"Today"},{"value":"week","label":"This Week"},{"value":"month","label":"This Month"},{"value":"year","label":"This Year"}]}}', 200, 'Dashboard filter options')]
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'periods' => EnumOptions::make(DashboardPeriodEnum::class, 'dashboard_periods'),
        ]);
    }
}
