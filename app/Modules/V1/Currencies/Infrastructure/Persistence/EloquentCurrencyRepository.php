<?php

namespace App\Modules\V1\Currencies\Infrastructure\Persistence;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\Currencies\Domain\Repositories\CurrencyRepositoryInterface;
use App\Modules\V1\Currencies\Infrastructure\Filters\CurrencyFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Currency::filter($filters, CurrencyFilter::class)
            ->with('translations')
            ->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(array $filters): Collection
    {
        return Currency::filter($filters, CurrencyFilter::class)
            ->where('is_active', true)
            ->withTranslation()
            ->orderByTranslation('name')
            ->get();
    }

    public function create(array $attributes): Currency
    {
        $currency = Currency::query()->create(
            $this->withTranslations($attributes),
        );

        return $currency->load('translations');
    }

    public function findOrFail(int $id): Currency
    {
        return Currency::query()->findOrFail($id);
    }

    public function update(Currency $currency, array $attributes): Currency
    {
        $currency->fill($this->withTranslations($attributes));
        $currency->save();

        return $currency->refresh()->load('translations');
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
