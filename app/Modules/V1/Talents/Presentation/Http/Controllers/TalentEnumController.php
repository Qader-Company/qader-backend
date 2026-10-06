<?php

namespace App\Modules\V1\Talents\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Talents\Application\UseCases\ListTalentEnumOptionsUseCase;
use App\Modules\V1\Talents\Domain\ValueObjects\GenderEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\ReligionEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Modules\V1\Talents\Presentation\Http\Resources\TalentEnumOptionResource;
use Illuminate\Http\JsonResponse;

class TalentEnumController extends Controller
{
    public function genders(ListTalentEnumOptionsUseCase $listOptions): JsonResponse
    {
        return $this->response(
            $listOptions,
            GenderEnum::class,
            'genders',
        );
    }

    public function religions(ListTalentEnumOptionsUseCase $listOptions): JsonResponse
    {
        return $this->response(
            $listOptions,
            ReligionEnum::class,
            'religions',
        );
    }

    public function languageProficiencies(
        ListTalentEnumOptionsUseCase $listOptions,
    ): JsonResponse {
        return $this->response(
            $listOptions,
            LanguageProficiencyEnum::class,
            'language_proficiencies',
        );
    }

    public function seniorityLevels(
        ListTalentEnumOptionsUseCase $listOptions,
    ): JsonResponse {
        return $this->response(
            $listOptions,
            SeniorityLevelEnum::class,
            'seniority_levels',
        );
    }

    public function workTypes(ListTalentEnumOptionsUseCase $listOptions): JsonResponse
    {
        return $this->response(
            $listOptions,
            WorkTypeEnum::class,
            'work_types',
        );
    }

    public function workModes(ListTalentEnumOptionsUseCase $listOptions): JsonResponse
    {
        return $this->response(
            $listOptions,
            WorkModeEnum::class,
            'work_modes',
        );
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function response(
        ListTalentEnumOptionsUseCase $listOptions,
        string $enumClass,
        string $translationKey,
    ): JsonResponse {
        $options = $listOptions->execute(
            $enumClass,
            $translationKey,
        );
        $resource = TalentEnumOptionResource::collection($options);

        return ApiResponse::success($resource);
    }
}
