<?php

namespace App\Modules\V1\Languages\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Languages\Application\UseCases\CreateLanguageUseCase;
use App\Modules\V1\Languages\Application\UseCases\ListLanguagesUseCase;
use App\Modules\V1\Languages\Application\UseCases\UpdateLanguageUseCase;
use App\Modules\V1\Languages\Presentation\Http\Requests\ListLanguagesRequest;
use App\Modules\V1\Languages\Presentation\Http\Requests\StoreLanguageRequest;
use App\Modules\V1\Languages\Presentation\Http\Requests\UpdateLanguageRequest;
use App\Modules\V1\Languages\Presentation\Http\Resources\LanguageResource;
use Illuminate\Http\JsonResponse;

class AdminLanguageController extends Controller
{
    public function index(
        ListLanguagesRequest $request,
        ListLanguagesUseCase $listLanguages,
    ): JsonResponse {
        $languages = $listLanguages->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            LanguageResource::collection($languages),
        );
    }

    public function store(
        StoreLanguageRequest $request,
        CreateLanguageUseCase $createLanguage,
    ): JsonResponse {
        $language = $createLanguage->execute($request->validated());

        return ApiResponse::created(new LanguageResource($language));
    }

    public function update(
        UpdateLanguageRequest $request,
        int $record,
        UpdateLanguageUseCase $updateLanguage,
    ): JsonResponse {
        $language = $updateLanguage->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(new LanguageResource($language));
    }
}
