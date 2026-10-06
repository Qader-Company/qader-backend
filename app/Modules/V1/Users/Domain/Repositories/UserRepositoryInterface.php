<?php

namespace App\Modules\V1\Users\Domain\Repositories;

use App\Modules\V1\Users\Domain\Models\User;

interface UserRepositoryInterface
{
    public function update(User $user, array $attributes): User;

    public function findWhere(array $attributes): ?User;
}
