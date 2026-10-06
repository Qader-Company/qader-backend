<?php

namespace App\Modules\V1\Cities\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Cities\Application\UseCases\CreateCityUseCase;
use App\Modules\V1\Cities\Application\UseCases\ListCitiesUseCase;
use App\Modules\V1\Cities\Application\UseCases\UpdateCityUseCase;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Cities\Presentation\Http\Requests\ListCitiesRequest;
use App\Modules\V1\Cities\Presentation\Http\Requests\StoreCityRequest;
use App\Modules\V1\Cities\Presentation\Http\Requests\UpdateCityRequest;
use App\Modules\V1\Cities\Presentation\Http\Resources\CityResource;

class AdminCityController extends Controller
{
    public function index(
        ListCitiesRequest $request,
        ListCitiesUseCase $listCities,
    ) {
        $cities = $listCities->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(CityResource::collection($cities));
    }

    public function store(StoreCityRequest $request, CreateCityUseCase $createCity)
    {
        $city = $createCity->execute($request->validated());

        return ApiResponse::created(new CityResource($city));
    }

    public function update(UpdateCityRequest $request, City $city, UpdateCityUseCase $updateCity)
    {
        $city = $updateCity->execute($city, $request->validated());

        return ApiResponse::updated(new CityResource($city));
    }
}
