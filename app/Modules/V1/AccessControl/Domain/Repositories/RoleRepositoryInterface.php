<?php

namespace App\Modules\V1\AccessControl\Domain\Repositories;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function list(PortalTypeEnum $portal): Collection;

    public function create(
        array $attributes,
        array $permissions
    ): object;

    public function update(
        int $roleId,
        PortalTypeEnum $portal,
        array $attributes,
        array $permissions
    ): object;

    public function delete(
        int $roleId,
        PortalTypeEnum $portal
    ): void;

    public function hasName(
        int $roleId,
        PortalTypeEnum $portal,
        string $name
    ): bool;
}
