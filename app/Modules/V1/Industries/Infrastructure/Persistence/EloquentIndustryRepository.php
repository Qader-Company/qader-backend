<?php

namespace App\Modules\V1\Industries\Infrastructure\Persistence;

use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\Industries\Domain\Repositories\IndustryRepositoryInterface;
use App\Modules\V1\Industries\Infrastructure\Filters\IndustryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentIndustryRepository implements IndustryRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Industry::filter($filters, IndustryFilter::class)
            ->with('translations')->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(): Collection
    {
        return Industry::query()->withTranslation()->where('is_active', true)
            ->orderByTranslation('name')->get();
    }

    public function create(array $attributes): Industry
    {
        return Industry::query()->create($this->withTranslations($attributes))->load('translations');
    }

    public function update(Industry $industry, array $attributes): Industry
    {
        $industry->fill($this->withTranslations($attributes))->save();

        return $industry->refresh()->load('translations');
    }

    private function withTranslations(array $attributes): array
    {
        $name = $attributes['name'] ?? null;
        unset($attributes['name']);

        if (is_array($name)) {
            $attributes['en'] = ['name' => $name['en']];
            $attributes['ar'] = ['name' => $name['ar']];
        }

        return $attributes;
    }
}
