<?php

namespace App\Modules\V1\Languages\Infrastructure\Persistence;

use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Languages\Domain\Repositories\LanguageRepositoryInterface;
use App\Modules\V1\Languages\Infrastructure\Filters\LanguageFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentLanguageRepository implements LanguageRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Language::filter($filters, LanguageFilter::class)
            ->with('translations')
            ->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(array $filters): Collection
    {
        return Language::filter($filters, LanguageFilter::class)
            ->where('is_active', true)
            ->withTranslation()
            ->orderByTranslation('name')
            ->get();
    }

    public function create(array $attributes): Language
    {
        $language = Language::query()->create(
            $this->withTranslations($attributes),
        );

        return $language->load('translations');
    }

    public function findOrFail(int $id): Language
    {
        return Language::query()->findOrFail($id);
    }

    public function update(Language $language, array $attributes): Language
    {
        $language->fill($this->withTranslations($attributes));
        $language->save();

        return $language->refresh()->load('translations');
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
