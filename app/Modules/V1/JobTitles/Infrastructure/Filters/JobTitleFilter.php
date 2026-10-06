<?php

namespace App\Modules\V1\JobTitles\Infrastructure\Filters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class JobTitleFilter extends ModelFilter
{
    public function setup(): void
    {
        if (
            ! $this->input('only_active_professional_fields', false)
            || $this->input('professional_field_id')
        ) {
            return;
        }

        $this->whereHas(
            'professionalFields',
            fn (Builder $query) => $query->where('is_active', true),
        );
    }

    public function search(string $search): void
    {
        $this->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    public function professionalField(int $professionalFieldId): void
    {
        $this->whereHas(
            'professionalFields',
            function (Builder $query) use ($professionalFieldId): void {
                $query->whereKey($professionalFieldId);

                if ($this->input('only_active_professional_fields', false)) {
                    $query->where('is_active', true);
                }
            },
        );
    }

    public function isActive(bool $isActive): void
    {
        $this->where('is_active', $isActive);
    }
}
