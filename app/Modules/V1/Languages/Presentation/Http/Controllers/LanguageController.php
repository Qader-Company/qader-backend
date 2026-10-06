<?php

namespace App\Modules\V1\Languages\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Languages\Application\UseCases\ListLanguagesUseCase;
use App\Modules\V1\Languages\Presentation\Http\Requests\ListActiveLanguagesRequest;
use App\Modules\V1\Languages\Presentation\Http\Resources\LanguageResource;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(
        ListActiveLanguagesRequest $request,
        ListLanguagesUseCase $listLanguages,
    ): JsonResponse {
        $languages = $listLanguages->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            LanguageResource::collection($languages),
        );
    }
}
