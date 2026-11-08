<?php

namespace App\Modules\V1\Talents\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Application\UseCases\GetTalentProfileUseCase;
use App\Modules\V1\Talents\Application\UseCases\SaveTalentOnboardingStepUseCase;
use App\Modules\V1\Talents\Application\UseCases\SelectTalentProfessionalFieldUseCase;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Presentation\Http\Requests\SaveTalentOnboardingStepRequest;
use App\Modules\V1\Talents\Presentation\Http\Requests\SelectProfessionalFieldRequest;
use App\Modules\V1\Talents\Presentation\Http\Resources\TalentOnboardingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentOnboardingController extends Controller
{
    public function selectProfessionalField(
        SelectProfessionalFieldRequest $request,
        GetTalentProfileUseCase $profiles,
        SelectTalentProfessionalFieldUseCase $selectProfessionalField,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $result = $selectProfessionalField->execute(
            $profile,
            $request->integer('professional_field_id'),
        );

        return ApiResponse::updated(new TalentOnboardingResource(
            $result['profile'],
            $result['state'],
        ));
    }

    public function show(
        Request $request,
        GetTalentProfileUseCase $profiles,
        TalentProfileProgressService $progress,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $state = $progress->calculate($profile);
        $resource = new TalentOnboardingResource(
            $profile,
            $state,
        );

        return ApiResponse::success($resource);
    }

    public function saveStep(
        SaveTalentOnboardingStepRequest $request,
        string $step,
        GetTalentProfileUseCase $profiles,
        SaveTalentOnboardingStepUseCase $saveStep,
    ): JsonResponse {
        $profile = $profiles->execute($request->user()->id);
        $onboardingStep = OnboardingStepEnum::tryFrom($step);

        abort_unless($onboardingStep, 404);

        $result = $saveStep->execute(
            $profile,
            $onboardingStep,
            $request->validated(),
        );
        $resource = new TalentOnboardingResource(
            $result['profile'],
            $result['state'],
        );

        return ApiResponse::updated([
            'onboarding' => $resource->resolve($request),
            'application_created' => $result['applicationCreated'],
        ]);
    }
}
