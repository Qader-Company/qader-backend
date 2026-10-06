<?php

namespace App\Modules\V1\Countries\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Countries\Application\UseCases\CreateCountryUseCase;
use App\Modules\V1\Countries\Application\UseCases\ListCountriesUseCase;
use App\Modules\V1\Countries\Application\UseCases\UpdateCountryUseCase;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Countries\Presentation\Http\Requests\ListCountriesRequest;
use App\Modules\V1\Countries\Presentation\Http\Requests\StoreCountryRequest;
use App\Modules\V1\Countries\Presentation\Http\Requests\UpdateCountryRequest;
use App\Modules\V1\Countries\Presentation\Http\Resources\CountryResource;

class AdminCountryController extends Controller
{
    public function index(
        ListCountriesRequest $request,
        ListCountriesUseCase $listCountries,
    ) {
        $countries = $listCountries->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(CountryResource::collection($countries));
    }

    public function store(StoreCountryRequest $request, CreateCountryUseCase $createCountry)
    {
        $country = $createCountry->execute($request->validated());

        return ApiResponse::created(new CountryResource($country));
    }

    public function update(UpdateCountryRequest $request, Country $country, UpdateCountryUseCase $updateCountry)
    {
        $country = $updateCountry->execute($country, $request->validated());

        return ApiResponse::updated(new CountryResource($country));
    }
}
