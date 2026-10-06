<?php

namespace App\Modules\V1\Cities\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class CityFilter extends ModelFilter
{
    public function country(int $countryId): void
    {
        $this->where('country_id', $countryId);
    }

    public function search(string $search): void
    {
        $this->whereHas(
            'translations',
            function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            },
        );
    }

    public function isActive(bool $isActive): void
    {
        $this->where('is_active', $isActive);
    }
}
