<?php

namespace App\Modules\V1\Countries\Infrastructure\Persistence;

use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Countries\Domain\Repositories\CountryRepositoryInterface;
use App\Modules\V1\Countries\Infrastructure\Filters\CountryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentCountryRepository implements CountryRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Country::filter($filters, CountryFilter::class)
            ->with('translations')
            ->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(): Collection
    {
        return Country::query()
            ->withTranslation()
            ->where('is_active', true)
            ->orderByTranslation('name')
            ->get();
    }

    public function create(array $attributes): Country
    {
        $country = Country::query()->create(
            $this->withTranslations($attributes),
        );

        return $country->load('translations');
    }

    public function update(Country $country, array $attributes): Country
    {
        $country->fill($this->withTranslations($attributes));
        $country->save();

        return $country->refresh()->load('translations');
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
