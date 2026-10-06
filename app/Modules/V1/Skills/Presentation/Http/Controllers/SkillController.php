<?php

namespace App\Modules\V1\Skills\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Skills\Application\UseCases\ListSkillsUseCase;
use App\Modules\V1\Skills\Presentation\Http\Requests\ListActiveSkillsRequest;
use App\Modules\V1\Skills\Presentation\Http\Resources\SkillResource;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    public function index(
        ListActiveSkillsRequest $request,
        ListSkillsUseCase $listSkills,
    ): JsonResponse {
        $skills = $listSkills->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(SkillResource::collection($skills));
    }
}
