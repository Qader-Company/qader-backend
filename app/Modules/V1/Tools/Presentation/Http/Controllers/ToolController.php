<?php

namespace App\Modules\V1\Tools\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tools\Application\UseCases\ListToolsUseCase;
use App\Modules\V1\Tools\Presentation\Http\Requests\ListActiveToolsRequest;
use App\Modules\V1\Tools\Presentation\Http\Resources\ToolResource;
use Illuminate\Http\JsonResponse;

class ToolController extends Controller
{
    public function index(
        ListActiveToolsRequest $request,
        ListToolsUseCase $listTools,
    ): JsonResponse {
        $tools = $listTools->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(ToolResource::collection($tools));
    }
}
