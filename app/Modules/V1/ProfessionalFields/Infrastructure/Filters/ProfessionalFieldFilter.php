<?php

namespace App\Modules\V1\ProfessionalFields\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class ProfessionalFieldFilter extends ModelFilter
{
    public function search(string $search): void
    {
        $this->where(function (Builder $query) use ($search): void {
            $query
                ->whereHas('translations', function (Builder $translationQuery) use ($search): void {
                    $translationQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    public function isActive(bool $isActive): void
    {
        $this->where('is_active', $isActive);
    }
}
