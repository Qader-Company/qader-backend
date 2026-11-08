<?php

namespace App\Modules\V1\ContractTypes\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\ContractTypes\Application\Services\ContractTypeService;
use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\ContractTypes\Presentation\Http\Requests\UpdateContractTypeRequest;
use App\Modules\V1\ContractTypes\Presentation\Http\Requests\UpdateContractTypeStatusRequest;
use App\Modules\V1\ContractTypes\Presentation\Http\Resources\ContractTypeResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin
 *
 * @subgroup Contract Types
 */
class AdminContractTypeController extends Controller
{
    public function index(ContractTypeService $service): JsonResponse
    {
        return ApiResponse::success(ContractTypeResource::collection($service->all()));
    }

    public function show(ContractType $contractType, ContractTypeService $service): JsonResponse
    {
        return ApiResponse::success(new ContractTypeResource($service->show($contractType)));
    }

    public function update(UpdateContractTypeRequest $request, ContractType $contractType, ContractTypeService $service): JsonResponse
    {
        return ApiResponse::updated(new ContractTypeResource($service->update($contractType, $request->validated())));
    }

    public function status(UpdateContractTypeStatusRequest $request, ContractType $contractType, ContractTypeService $service): JsonResponse
    {
        return ApiResponse::updated(new ContractTypeResource($service->setStatus($contractType, $request->boolean('is_active'))));
    }
}
