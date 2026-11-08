<?php

namespace App\Modules\V1\ProfessionalFields\Infrastructure\Persistence;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Infrastructure\Filters\ProfessionalFieldFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentProfessionalFieldRepository implements ProfessionalFieldRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ProfessionalField::filter(
            $filters,
            ProfessionalFieldFilter::class,
        )
            ->with('translations')
            ->orderByTranslation('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function listActive(array $filters): Collection
    {
        return ProfessionalField::filter(
            $filters,
            ProfessionalFieldFilter::class,
        )
            ->where('is_active', true)
            ->withTranslation()
            ->orderByTranslation('name')
            ->get();
    }

    public function create(array $attributes): ProfessionalField
    {
        return DB::transaction(
            function () use ($attributes): ProfessionalField {
                $professionalField = ProfessionalField::query()->create(
                    $this->withTranslations($attributes),
                );

                return $professionalField->load('translations');
            },
        );
    }

    public function findOrFail(int $id): ProfessionalField
    {
        return ProfessionalField::query()->findOrFail($id);
    }

    public function update(
        ProfessionalField $professionalField,
        array $attributes,
    ): ProfessionalField {
        $professionalField->fill(
            $this->withTranslations($attributes),
        );
        $professionalField->save();

        return $professionalField->refresh()->load('translations');
    }

    private function withTranslations(array $attributes): array
    {
        $name = $attributes['name'] ?? null;
        $description = $attributes['description'] ?? null;
        unset($attributes['name']);
        unset($attributes['description']);

        if (is_array($name)) {
            $attributes['en'] = [
                'name' => $name['en'],
                'description' => $description['en'] ?? null,
            ];
            $attributes['ar'] = [
                'name' => $name['ar'],
                'description' => $description['ar'] ?? null,
            ];
        } elseif (is_array($description)) {
            $attributes['en'] = ['description' => $description['en']];
            $attributes['ar'] = ['description' => $description['ar']];
        }

        return $attributes;
    }
}
