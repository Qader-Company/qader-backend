<?php

namespace App\Modules\V1\Countries\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Countries\Application\UseCases\ListCountriesUseCase;
use App\Modules\V1\Countries\Presentation\Http\Resources\CountryResource;

class CountryController extends Controller
{
    public function index(ListCountriesUseCase $listCountries)
    {
        $countries = $listCountries->execute(forAdmin: false);

        return ApiResponse::success(CountryResource::collection($countries));
    }
}
