<?php

namespace App\Modules\V1\Talents\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use App\Modules\V1\Talents\Application\UseCases\UpdateTalentProfileUseCase;
use App\Modules\V1\Talents\Presentation\Http\Requests\UpdateTalentProfileRequest;
use App\Modules\V1\Talents\Presentation\Http\Resources\TalentProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentProfileController extends Controller
{
    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        TalentProfileProgressService $progress,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $onboardingState = $progress->calculate($profile);
        $resource = new TalentProfileResource($profile, $onboardingState);

        return ApiResponse::success($resource);
    }

    public function update(
        UpdateTalentProfileRequest $request,
        GetTalentProfileUseCase $profiles,
        UpdateTalentProfileUseCase $updateProfile,
    ): JsonResponse {
        $userId = $request->user()->id;
        $profile = $profiles->execute($userId);
        $validatedData = $request->validated();
        $result = $updateProfile->execute($profile, $validatedData);
        $resource = new TalentProfileResource(
            $result['profile'],
            $result['state'],
        );

        return ApiResponse::updated($resource->resolve($request));
    }
}
