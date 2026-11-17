<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
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
        $countries = HiringSupportRequest::query()
            ->select('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return ApiResponse::success([
            'countries' => $countries,
        ]);
    }
}
