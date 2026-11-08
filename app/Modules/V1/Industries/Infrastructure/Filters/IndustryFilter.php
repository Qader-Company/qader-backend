<?php

namespace App\Modules\V1\Industries\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class IndustryFilter extends ModelFilter
{
    public function search(string $search): void
    {
        $this->where(function (Builder $query) use ($search): void {
            $query->whereHas('translations', fn (Builder $translation): Builder => $translation->where('name', 'like', "%{$search}%"))
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    public function isActive(bool $isActive): void
    {
        $this->where('is_active', $isActive);
    }
}
