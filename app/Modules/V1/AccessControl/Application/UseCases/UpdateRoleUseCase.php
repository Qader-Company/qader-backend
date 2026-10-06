<?php

namespace App\Modules\V1\AccessControl\Application\UseCases;

use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles
    ) {}

    public function execute(
        int $roleId,
        array $attributes,
        PortalTypeEnum $portal
    ): object {
        if ($this->roles->hasName($roleId, $portal, 'owner')) {
            throw new DomainException(
                __('admins.owner_role_cannot_be_modified')
            );
        }

        if (strcasecmp(trim($attributes['name']), 'owner') === 0) {
            throw new DomainException(
                __('admins.owner_role_is_reserved')
            );
        }

        $permissions = Arr::pull($attributes, 'permissions');

        return DB::transaction(
            fn (): object => $this->roles->update(
                $roleId,
                $portal,
                $attributes,
                $permissions
            )
        );
    }
}
