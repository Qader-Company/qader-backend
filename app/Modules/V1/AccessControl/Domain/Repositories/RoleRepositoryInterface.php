<?php

namespace App\Modules\V1\AccessControl\Domain\Repositories;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function list(PortalTypeEnum $portal, array $filters = []): Collection;

    public function findOrFail(
        int $roleId,
        PortalTypeEnum $portal
    ): object;

    public function create(
        array $attributes,
        ?array $permissions
    ): object;

    public function updateStatus(
        int $roleId,
        PortalTypeEnum $portal,
        bool $isActive
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

    public function isAssigned(
        int $roleId,
        PortalTypeEnum $portal
    ): bool;
}
