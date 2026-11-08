<?php

namespace App\Modules\V1\Industries\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Industries\Application\UseCases\CreateIndustryUseCase;
use App\Modules\V1\Industries\Application\UseCases\ListIndustriesUseCase;
use App\Modules\V1\Industries\Application\UseCases\UpdateIndustryUseCase;
use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\Industries\Presentation\Http\Requests\ListIndustriesRequest;
use App\Modules\V1\Industries\Presentation\Http\Requests\StoreIndustryRequest;
use App\Modules\V1\Industries\Presentation\Http\Requests\UpdateIndustryRequest;
use App\Modules\V1\Industries\Presentation\Http\Resources\IndustryResource;

class AdminIndustryController extends Controller
{
    public function index(ListIndustriesRequest $request, ListIndustriesUseCase $useCase)
    {
        return ApiResponse::success(IndustryResource::collection($useCase->execute(true, $request->validated())));
    }

    public function store(StoreIndustryRequest $request, CreateIndustryUseCase $useCase)
    {
        return ApiResponse::created(new IndustryResource($useCase->execute($request->validated())));
    }

    public function update(UpdateIndustryRequest $request, Industry $industry, UpdateIndustryUseCase $useCase)
    {
        return ApiResponse::updated(new IndustryResource($useCase->execute($industry, $request->validated())));
    }
}
