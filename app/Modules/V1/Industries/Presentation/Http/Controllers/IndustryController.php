<?php

namespace App\Modules\V1\Industries\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Industries\Application\UseCases\ListIndustriesUseCase;
use App\Modules\V1\Industries\Presentation\Http\Resources\IndustryResource;

class IndustryController extends Controller
{
    public function index(ListIndustriesUseCase $useCase)
    {
        return ApiResponse::success(IndustryResource::collection($useCase->execute(false)));
    }
}
