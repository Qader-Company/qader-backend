<?php

namespace App\Modules\V1\Skills\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Skills\Application\UseCases\CreateSkillUseCase;
use App\Modules\V1\Skills\Application\UseCases\ListSkillsUseCase;
use App\Modules\V1\Skills\Application\UseCases\UpdateSkillUseCase;
use App\Modules\V1\Skills\Presentation\Http\Requests\ListSkillsRequest;
use App\Modules\V1\Skills\Presentation\Http\Requests\StoreSkillRequest;
use App\Modules\V1\Skills\Presentation\Http\Requests\UpdateSkillRequest;
use App\Modules\V1\Skills\Presentation\Http\Resources\SkillResource;
use Illuminate\Http\JsonResponse;

class AdminSkillController extends Controller
{
    public function index(
        ListSkillsRequest $request,
        ListSkillsUseCase $listSkills,
    ): JsonResponse {
        $skills = $listSkills->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(SkillResource::collection($skills));
    }

    public function store(
        StoreSkillRequest $request,
        CreateSkillUseCase $createSkill,
    ): JsonResponse {
        $skill = $createSkill->execute($request->validated());

        return ApiResponse::created(new SkillResource($skill));
    }

    public function update(
        UpdateSkillRequest $request,
        int $record,
        UpdateSkillUseCase $updateSkill,
    ): JsonResponse {
        $skill = $updateSkill->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(new SkillResource($skill));
    }
}
