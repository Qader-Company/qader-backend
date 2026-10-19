<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\UseCases\GetTalentApplicationUseCase;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentApplicationResource;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentApplicationController extends Controller
{
    /**
     * @response 404 scenario="Application not submitted" {"success":false,"message":"No application has been submitted yet."}
     * @response scenario="Application submitted" {"success":true,"data":{"id":1,"status":"submitted","submitted_at":"2026-10-19T08:45:00.000000Z","profile_completion_at_submission":100,"snapshot_version":1,"snapshot_captured_at":"2026-10-19T08:45:00.000000Z","submitted_cv":{"id":10,"file_name":"cv.pdf","download_url":"http://localhost:8000/api/v1/talent/profile/media/10/download"}}}
     */
    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        GetTalentApplicationUseCase $applications,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $application = $applications->execute($profile->id);
        $application->load('media');
        $resource = new TalentApplicationResource($application);

        return ApiResponse::success($resource->resolve($request));
    }
}
