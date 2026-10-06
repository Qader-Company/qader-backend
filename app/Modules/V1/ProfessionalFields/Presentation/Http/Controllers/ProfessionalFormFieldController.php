<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\ProfessionalFields\Application\UseCases\ListProfessionalFormFieldsUseCase;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\ListProfessionalFormFieldsRequest;
use Illuminate\Http\JsonResponse;

class ProfessionalFormFieldController extends Controller
{
    public function index(
        ListProfessionalFormFieldsRequest $request,
        ListProfessionalFormFieldsUseCase $listFormFields,
    ): JsonResponse {
        $formFields = $listFormFields->forApplication(
            $request->integer('professional_field_id'),
        );

        return ApiResponse::success($formFields);
    }
}
