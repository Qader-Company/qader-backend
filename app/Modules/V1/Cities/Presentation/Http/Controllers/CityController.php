<?php

namespace App\Modules\V1\Cities\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Cities\Application\UseCases\ListCitiesUseCase;
use App\Modules\V1\Cities\Presentation\Http\Requests\ListActiveCitiesRequest;
use App\Modules\V1\Cities\Presentation\Http\Resources\CityResource;

class CityController extends Controller
{
    public function index(
        ListActiveCitiesRequest $request,
        ListCitiesUseCase $listCities,
    ) {
        $cities = $listCities->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(CityResource::collection($cities));
    }
}
