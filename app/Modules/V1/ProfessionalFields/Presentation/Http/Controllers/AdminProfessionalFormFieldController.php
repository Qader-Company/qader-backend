<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\ProfessionalFields\Application\UseCases\ListProfessionalFormFieldsUseCase;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Requests\ListAdminProfessionalFormFieldsRequest;
use Illuminate\Http\JsonResponse;

class AdminProfessionalFormFieldController extends Controller
{
    public function index(
        ListAdminProfessionalFormFieldsRequest $request,
        ListProfessionalFormFieldsUseCase $listFormFields,
    ): JsonResponse {
        $formFields = $listFormFields->forAdmin(
            $request->integer('professional_field_id') ?: null,
        );

        return ApiResponse::success($formFields);
    }
}
