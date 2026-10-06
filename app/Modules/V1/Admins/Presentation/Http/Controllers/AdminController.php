<?php

namespace App\Modules\V1\Admins\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Admins\Application\UseCases\CreateAdminUseCase;
use App\Modules\V1\Admins\Application\UseCases\GetAdminUseCase;
use App\Modules\V1\Admins\Application\UseCases\ListAdminsUseCase;
use App\Modules\V1\Admins\Application\UseCases\UpdateAdminStatusUseCase;
use App\Modules\V1\Admins\Application\UseCases\UpdateAdminUseCase;
use App\Modules\V1\Admins\Presentation\Http\Requests\ListAdminsRequest;
use App\Modules\V1\Admins\Presentation\Http\Requests\StoreAdminRequest;
use App\Modules\V1\Admins\Presentation\Http\Requests\UpdateAdminRequest;
use App\Modules\V1\Admins\Presentation\Http\Requests\UpdateAdminStatusRequest;
use App\Modules\V1\Admins\Presentation\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function index(
        ListAdminsRequest $request,
        ListAdminsUseCase $listAdmins
    ): JsonResponse {
        $admins = $listAdmins->execute($request->validated());

        return ApiResponse::success(
            AdminResource::collection($admins)
        );
    }

    public function show(
        int $admin,
        GetAdminUseCase $getAdmin
    ): JsonResponse {
        $adminData = $getAdmin->execute($admin);

        return ApiResponse::success(
            new AdminResource($adminData)
        );
    }

    public function store(
        StoreAdminRequest $request,
        CreateAdminUseCase $createAdmin
    ): JsonResponse {
        $admin = $createAdmin->execute($request->validated());

        return ApiResponse::created(
            new AdminResource($admin)
        );
    }

    public function update(
        UpdateAdminRequest $request,
        int $admin,
        UpdateAdminUseCase $updateAdmin
    ): JsonResponse {
        $adminData = $updateAdmin->execute(
            $admin,
            $request->validated()
        );

        return ApiResponse::updated(
            new AdminResource($adminData)
        );
    }

    public function updateStatus(
        UpdateAdminStatusRequest $request,
        int $admin,
        UpdateAdminStatusUseCase $updateAdminStatus
    ): JsonResponse {
        $adminData = $updateAdminStatus->execute(
            $admin,
            $request->boolean('is_active'),
            $request->user()->getKey()
        );

        return ApiResponse::updated(
            new AdminResource($adminData)
        );
    }
}
