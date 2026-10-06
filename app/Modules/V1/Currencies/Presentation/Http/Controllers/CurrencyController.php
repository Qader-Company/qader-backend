<?php

namespace App\Modules\V1\Currencies\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Currencies\Application\UseCases\ListCurrenciesUseCase;
use App\Modules\V1\Currencies\Presentation\Http\Requests\ListActiveCurrenciesRequest;
use App\Modules\V1\Currencies\Presentation\Http\Resources\CurrencyResource;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function index(
        ListActiveCurrenciesRequest $request,
        ListCurrenciesUseCase $listCurrencies,
    ): JsonResponse {
        $currencies = $listCurrencies->execute(
            forAdmin: false,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            CurrencyResource::collection($currencies),
        );
    }
}
