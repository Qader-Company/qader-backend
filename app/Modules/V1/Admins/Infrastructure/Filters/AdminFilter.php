<?php

namespace App\Modules\V1\Admins\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class AdminFilter extends ModelFilter
{
    public function search(string $search): void
    {
        $this->where(function (Builder $query) use ($search): void {
            $query
                ->where('display_name_ar', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhereHas('user', function (Builder $query) use ($search): void {
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

    public function roleId(int $roleId): void
    {
        $this->whereHas(
            'user.roles',
            fn (Builder $query) => $query->whereKey($roleId),
        );
    }
}
