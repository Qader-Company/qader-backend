<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\UseCases\CreateRoleUseCase;
use App\Modules\V1\AccessControl\Application\UseCases\DeleteRoleUseCase;
use App\Modules\V1\AccessControl\Application\UseCases\ListPermissionsUseCase;
use App\Modules\V1\AccessControl\Application\UseCases\ListRolesUseCase;
use App\Modules\V1\AccessControl\Application\UseCases\UpdateRoleUseCase;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\PermissionGroupResource;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\RoleResource;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Http\JsonResponse;

class AccessControlController extends Controller
{
    public function permissions(ListPermissionsUseCase $listPermissions): JsonResponse
    {
        $permissions = $listPermissions->execute(
            PortalTypeEnum::ADMIN
        );

        return ApiResponse::success(
            PermissionGroupResource::collection($permissions)
        );
    }

    public function roles(ListRolesUseCase $listRoles): JsonResponse
    {
        $roles = $listRoles->execute(PortalTypeEnum::ADMIN);

        return ApiResponse::success(
            RoleResource::collection($roles)
        );
    }

    public function storeRole(StoreRoleRequest $request, CreateRoleUseCase $createRole): JsonResponse
    {
        $role = $createRole->execute(
            $request->validated(),
            PortalTypeEnum::ADMIN
        );

        return ApiResponse::created(
            new RoleResource($role)
        );
    }

    public function updateRole(UpdateRoleRequest $request, int $role, UpdateRoleUseCase $updateRole): JsonResponse
    {
        $roleData = $updateRole->execute(
            $role,
            $request->validated(),
            PortalTypeEnum::ADMIN
        );

        return ApiResponse::updated(
            new RoleResource($roleData)
        );
    }

    public function destroyRole(int $role, DeleteRoleUseCase $deleteRole): JsonResponse
    {
        $deleteRole->execute(
            $role,
            PortalTypeEnum::ADMIN
        );

        return ApiResponse::deleted();
    }
}
