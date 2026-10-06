<?php

namespace App\Modules\V1\Tools\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Tools\Application\UseCases\CreateToolUseCase;
use App\Modules\V1\Tools\Application\UseCases\ListToolsUseCase;
use App\Modules\V1\Tools\Application\UseCases\UpdateToolUseCase;
use App\Modules\V1\Tools\Presentation\Http\Requests\ListToolsRequest;
use App\Modules\V1\Tools\Presentation\Http\Requests\StoreToolRequest;
use App\Modules\V1\Tools\Presentation\Http\Requests\UpdateToolRequest;
use App\Modules\V1\Tools\Presentation\Http\Resources\ToolResource;
use Illuminate\Http\JsonResponse;

class AdminToolController extends Controller
{
    public function index(
        ListToolsRequest $request,
        ListToolsUseCase $listTools,
    ): JsonResponse {
        $tools = $listTools->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(ToolResource::collection($tools));
    }

    public function store(
        StoreToolRequest $request,
        CreateToolUseCase $createTool,
    ): JsonResponse {
        $tool = $createTool->execute($request->validated());

        return ApiResponse::created(new ToolResource($tool));
    }

    public function update(
        UpdateToolRequest $request,
        int $record,
        UpdateToolUseCase $updateTool,
    ): JsonResponse {
        $tool = $updateTool->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(new ToolResource($tool));
    }
}
