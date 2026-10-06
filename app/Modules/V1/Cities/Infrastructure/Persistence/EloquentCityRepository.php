<?php

namespace App\Modules\V1\Cities\Infrastructure\Persistence;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Cities\Domain\Repositories\CityRepositoryInterface;
use App\Modules\V1\Cities\Infrastructure\Filters\CityFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentCityRepository implements CityRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return City::filter($filters, CityFilter::class)
            ->with(['translations', 'country.translations'])
            ->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(array $filters): Collection
    {
        return City::filter($filters, CityFilter::class)
            ->where('is_active', true)
            ->withTranslation()
            ->orderByTranslation('name')
            ->get();
    }

    public function create(array $attributes): City
    {
        $city = City::query()->create(
            $this->withTranslations($attributes),
        );

        return $city->load(['translations', 'country.translations']);
    }

    public function update(City $city, array $attributes): City
    {
        $city->fill($this->withTranslations($attributes));
        $city->save();

        return $city->refresh()->load(['translations', 'country.translations']);
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
