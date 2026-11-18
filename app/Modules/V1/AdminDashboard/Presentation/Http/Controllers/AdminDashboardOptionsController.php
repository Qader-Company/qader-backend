<?php

namespace App\Modules\V1\AdminDashboard\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AdminDashboard\Domain\ValueObjects\DashboardPeriodEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin
 *
 * @subgroup Dashboard
 */
class AdminDashboardOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'periods' => EnumOptions::make(DashboardPeriodEnum::class, 'dashboard_periods'),
        ]);
    }
}
