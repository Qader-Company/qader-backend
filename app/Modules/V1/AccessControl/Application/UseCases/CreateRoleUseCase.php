<?php

namespace App\Modules\V1\AccessControl\Application\UseCases;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles
    ) {}

    public function execute(
        array $attributes,
        PortalTypeEnum $portal
    ): object {
        if (strcasecmp(trim($attributes['name']), 'owner') === 0) {
            throw new DomainException(
                __('admins.owner_role_is_reserved')
            );
        }

        $permissions = Arr::pull($attributes, 'permissions');
        $attributes['guard_name'] = PermissionRegistry::GUARD;
        $attributes['portal'] = $portal->value;

        return DB::transaction(
            fn (): object => $this->roles->create(
                $attributes,
                $permissions
            )
        );
    }
}
