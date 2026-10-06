<?php

namespace App\Modules\V1\Users\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function findWhere(array $attributes): ?User
    {
        return User::query()
            ->where($attributes)
            ->first();
    }
}
