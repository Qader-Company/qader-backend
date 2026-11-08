<?php

namespace App\Modules\V1\AccessControl\Application\UseCases;

use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use DomainException;
use Illuminate\Support\Facades\DB;

class DeleteRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles
    ) {}

    public function execute(
        int $roleId,
        PortalTypeEnum $portal
    ): void {
        $role = $this->roles->findOrFail($roleId, $portal);

        if ($role->name === AdminRoleEnum::SUPER_ADMIN->value) {
            throw new DomainException(
                __('admins.super_admin_role_cannot_be_deleted')
            );
        }

        if ($this->roles->isAssigned($roleId, $portal)) {
            throw new DomainException(
                __('admins.assigned_role_cannot_be_deleted')
            );
        }

        DB::transaction(
            fn () => $this->roles->delete($roleId, $portal)
        );
    }
}
