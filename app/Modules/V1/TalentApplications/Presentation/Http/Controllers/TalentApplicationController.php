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
    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        GetTalentApplicationUseCase $applications,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $application = $applications->execute($profile->id);
        $resource = new TalentApplicationResource($application);

        return ApiResponse::success($resource->resolve($request));
    }
}
