<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Website\Domain\Models\HiringSupportRequest;
use App\Modules\V1\Website\Presentation\Http\Requests\StoreHiringSupportRequest;
use Illuminate\Http\JsonResponse;

class PublicHiringSupportRequestController extends Controller
{
    public function store(StoreHiringSupportRequest $request): JsonResponse
    {
        $supportRequest = HiringSupportRequest::query()->create($request->validated());

        return ApiResponse::created([
            'id' => $supportRequest->id,
        ], __('website.hiring_support_request_created'));
    }

    public function getCountries(): JsonResponse
    {
        // `name` lives in country_translations (Translatable), not in the countries table.
        $countries = Country::query()
            ->with('translations')
            ->get()
            ->map(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return ApiResponse::success([
            'countries' => $countries,
        ]);
    }
}
