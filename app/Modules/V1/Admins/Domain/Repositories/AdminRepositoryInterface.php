<?php

namespace App\Modules\V1\Admins\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function findOrFail(int $adminId): object;

    public function create(array $attributes, array $roleIds): object;

    public function update(
        int $adminId,
        array $attributes,
        ?array $roleIds = null
    ): object;

    public function updateStatus(int $adminId, bool $isActive): object;

    public function hasOwnerRole(int $adminId): bool;

    public function belongsToUser(int $adminId, int $userId): bool;
}
