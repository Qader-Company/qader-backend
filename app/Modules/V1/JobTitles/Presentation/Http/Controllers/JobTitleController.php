<?php

namespace App\Modules\V1\JobTitles\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\JobTitles\Application\UseCases\ListJobTitlesUseCase;
use App\Modules\V1\JobTitles\Presentation\Http\Requests\ListActiveJobTitlesRequest;
use App\Modules\V1\JobTitles\Presentation\Http\Resources\JobTitleResource;
use Illuminate\Http\JsonResponse;

class JobTitleController extends Controller
{
    public function index(
        ListActiveJobTitlesRequest $request,
        ListJobTitlesUseCase $listJobTitles,
    ): JsonResponse {
        $jobTitles = $listJobTitles->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            JobTitleResource::collection($jobTitles),
        );
    }
}
