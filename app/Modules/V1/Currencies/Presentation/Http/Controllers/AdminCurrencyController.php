<?php

namespace App\Modules\V1\Currencies\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Currencies\Application\UseCases\CreateCurrencyUseCase;
use App\Modules\V1\Currencies\Application\UseCases\ListCurrenciesUseCase;
use App\Modules\V1\Currencies\Application\UseCases\UpdateCurrencyUseCase;
use App\Modules\V1\Currencies\Presentation\Http\Requests\ListCurrenciesRequest;
use App\Modules\V1\Currencies\Presentation\Http\Requests\StoreCurrencyRequest;
use App\Modules\V1\Currencies\Presentation\Http\Requests\UpdateCurrencyRequest;
use App\Modules\V1\Currencies\Presentation\Http\Resources\CurrencyResource;
use Illuminate\Http\JsonResponse;

class AdminCurrencyController extends Controller
{
    public function index(
        ListCurrenciesRequest $request,
        ListCurrenciesUseCase $listCurrencies,
    ): JsonResponse {
        $currencies = $listCurrencies->execute(
            forAdmin: true,
            filters: $request->validated(),
        );

        return ApiResponse::success(
            CurrencyResource::collection($currencies),
        );
    }

    public function store(
        StoreCurrencyRequest $request,
        CreateCurrencyUseCase $createCurrency,
    ): JsonResponse {
        $currency = $createCurrency->execute($request->validated());

        return ApiResponse::created(new CurrencyResource($currency));
    }

    public function update(
        UpdateCurrencyRequest $request,
        int $record,
        UpdateCurrencyUseCase $updateCurrency,
    ): JsonResponse {
        $currency = $updateCurrency->execute(
            $record,
            $request->validated(),
        );

        return ApiResponse::updated(new CurrencyResource($currency));
    }
}
