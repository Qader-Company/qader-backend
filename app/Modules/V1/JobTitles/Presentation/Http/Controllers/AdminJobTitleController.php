<?php

namespace App\Modules\V1\JobTitles\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\JobTitles\Application\UseCases\CreateJobTitleUseCase;
use App\Modules\V1\JobTitles\Application\UseCases\ListJobTitlesUseCase;
use App\Modules\V1\JobTitles\Application\UseCases\UpdateJobTitleUseCase;
use App\Modules\V1\JobTitles\Presentation\Http\Requests\ListJobTitlesRequest;
use App\Modules\V1\JobTitles\Presentation\Http\Requests\StoreJobTitleRequest;
use App\Modules\V1\JobTitles\Presentation\Http\Requests\UpdateJobTitleRequest;
use App\Modules\V1\JobTitles\Presentation\Http\Resources\JobTitleResource;
use Illuminate\Http\JsonResponse;

class AdminJobTitleController extends Controller
{
    public function index(
        ListJobTitlesRequest $request,
        ListJobTitlesUseCase $listJobTitles,
    ): JsonResponse {
        $jobTitles = $listJobTitles->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            JobTitleResource::collection($jobTitles),
        );
    }

    public function store(
        StoreJobTitleRequest $request,
        CreateJobTitleUseCase $createJobTitle,
    ): JsonResponse {
        $jobTitle = $createJobTitle->execute($request->validated());

        return ApiResponse::created(new JobTitleResource($jobTitle));
    }

    public function update(
        UpdateJobTitleRequest $request,
        int $record,
        UpdateJobTitleUseCase $updateJobTitle,
    ): JsonResponse {
        $jobTitle = $updateJobTitle->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(new JobTitleResource($jobTitle));
    }
}
