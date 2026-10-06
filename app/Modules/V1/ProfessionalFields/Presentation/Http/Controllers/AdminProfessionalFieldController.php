<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\ProfessionalFields\Application\UseCases\CreateProfessionalFieldUseCase;
use App\Modules\V1\ProfessionalFields\Application\UseCases\ListProfessionalFieldsUseCase;
use App\Modules\V1\ProfessionalFields\Application\UseCases\UpdateProfessionalFieldUseCase;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\ListProfessionalFieldsRequest;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\StoreProfessionalFieldRequest;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\UpdateProfessionalFieldRequest;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Resources\ProfessionalFieldResource;
use Illuminate\Http\JsonResponse;

class AdminProfessionalFieldController extends Controller
{
    public function index(
        ListProfessionalFieldsRequest $request,
        ListProfessionalFieldsUseCase $listProfessionalFields,
    ): JsonResponse {
        $professionalFields = $listProfessionalFields->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            ProfessionalFieldResource::collection($professionalFields),
        );
    }

    public function store(
        StoreProfessionalFieldRequest $request,
        CreateProfessionalFieldUseCase $createProfessionalField,
    ): JsonResponse {
        $professionalField = $createProfessionalField->execute(
            $request->validated(),
        );

        return ApiResponse::created(
            new ProfessionalFieldResource($professionalField),
        );
    }

    public function update(
        UpdateProfessionalFieldRequest $request,
        int $record,
        UpdateProfessionalFieldUseCase $updateProfessionalField,
    ): JsonResponse {
        $professionalField = $updateProfessionalField->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(
            new ProfessionalFieldResource($professionalField),
        );
    }
}
