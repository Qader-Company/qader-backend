<?php

namespace App\Modules\V1\AccessControl\Application\UseCases;

use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
        $role = $this->roles->findOrFail($roleId, $portal);

        if ($role->name === AdminRoleEnum::SUPER_ADMIN->value) {
            throw new DomainException(
                __('admins.super_admin_role_cannot_be_modified')
            );
        }

        if (isset($attributes['name_en'])) {
            $attributes['name'] = Str::slug($attributes['name_en'], '_');
        }

        if (
            isset($attributes['name'])
            && $attributes['name'] === AdminRoleEnum::SUPER_ADMIN->value
            && $attributes['name'] !== $role->name
        ) {
            throw new DomainException(
                __('admins.super_admin_role_name_is_reserved')
            );
        }

        $permissions = array_key_exists('permissions', $attributes)
            ? Arr::pull($attributes, 'permissions')
            : null;

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
