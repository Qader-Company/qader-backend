<?php

namespace App\Modules\V1\Admins\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class AdminFilter extends ModelFilter
{
    public function search(string $search): void
    {
        $this->whereHas('user', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });
    }

    public function isActive(bool $isActive): void
    {
        $this->whereHas(
            'user',
            fn (Builder $query) => $query->where('is_active', $isActive),
        );
    }

    public function role(int $roleId): void
    {
        $this->whereHas(
            'user.roles',
            fn (Builder $query) => $query->whereKey($roleId),
        );
    }
}
