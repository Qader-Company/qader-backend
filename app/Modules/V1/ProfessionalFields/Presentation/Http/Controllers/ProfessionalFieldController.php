<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\ProfessionalFields\Application\UseCases\ListProfessionalFieldsUseCase;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\ListActiveProfessionalFieldsRequest;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Resources\ProfessionalFieldResource;
use Illuminate\Http\JsonResponse;

class ProfessionalFieldController extends Controller
{
    public function index(
        ListActiveProfessionalFieldsRequest $request,
        ListProfessionalFieldsUseCase $listProfessionalFields,
    ): JsonResponse {
        $professionalFields = $listProfessionalFields->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            ProfessionalFieldResource::collection($professionalFields),
        );
    }
}
