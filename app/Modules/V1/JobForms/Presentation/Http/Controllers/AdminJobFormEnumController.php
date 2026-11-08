<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobExperienceRangeEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobPublishingStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobSubmissionStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkModeEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkTypeEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;

class AdminJobFormEnumController extends Controller
{
    public function publishingStatuses(): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make(JobPublishingStatusEnum::class, 'job_publishing_statuses'));
    }

    public function submissionStatuses(): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make(JobSubmissionStatusEnum::class, 'job_submission_statuses'));
    }

    public function workTypes(): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make(JobWorkTypeEnum::class, 'job_work_types'));
    }

    public function workModes(): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make(JobWorkModeEnum::class, 'job_work_modes'));
    }

    public function experienceRanges(): JsonResponse
    {
        return ApiResponse::success(EnumOptions::make(JobExperienceRangeEnum::class, 'job_experience_ranges'));
    }
}
