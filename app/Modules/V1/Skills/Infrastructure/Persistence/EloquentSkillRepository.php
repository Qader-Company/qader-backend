<?php

namespace App\Modules\V1\Skills\Infrastructure\Persistence;

use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Skills\Domain\Repositories\SkillRepositoryInterface;
use App\Modules\V1\Skills\Infrastructure\Filters\SkillFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentSkillRepository implements SkillRepositoryInterface
{
    private const TABLE = 'skills';

    private const PIVOT_TABLE = 'professional_field_skill';

    private const PIVOT_FOREIGN_KEY = 'skill_id';

    public function paginate(array $filters): LengthAwarePaginator
    {
        $skills = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: true,
        )->paginate($filters['per_page'] ?? 20);

        $this->attachProfessionalFields(
            skills: $skills->getCollection(),
            includeInactive: true,
        );

        return $skills;
    }

    public function listActive(array $filters): Collection
    {
        $skills = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: false,
        )
            ->where(self::TABLE.'.is_active', true)
            ->get();

        $this->attachProfessionalFields(
            skills: $skills,
            includeInactive: false,
        );

        return $skills;
    }

    public function create(array $attributes): Skill
    {
        $professionalFieldIds = Arr::pull(
            $attributes,
            'professional_field_ids',
            [],
        );

        return DB::transaction(function () use (
            $attributes,
            $professionalFieldIds,
        ): Skill {
            $skill = Skill::query()->create($attributes);
            $skill->professionalFields()->sync($professionalFieldIds);

            return $this->loadProfessionalFields($skill);
        });
    }

    public function findOrFail(int $id): Skill
    {
        return Skill::query()->findOrFail($id);
    }

    public function update(Skill $skill, array $attributes): Skill
    {
        $shouldSyncProfessionalFields = array_key_exists(
            'professional_field_ids',
            $attributes,
        );
        $professionalFieldIds = Arr::pull(
            $attributes,
            'professional_field_ids',
            [],
        );

        return DB::transaction(function () use (
            $skill,
            $attributes,
            $shouldSyncProfessionalFields,
            $professionalFieldIds,
        ): Skill {
            $skill->update($attributes);

            if ($shouldSyncProfessionalFields) {
                $skill->professionalFields()->sync($professionalFieldIds);
            }

            return $this->loadProfessionalFields($skill->refresh());
        });
    }

    private function buildListQuery(
        array $filters,
        bool $includeInactiveProfessionalFields,
    ): QueryBuilder {
        $filters['only_active_professional_fields'] =
            ! $includeInactiveProfessionalFields;

        $filteredSkills = Skill::filter($filters, SkillFilter::class)
            ->select(self::TABLE.'.id');

        return DB::table(self::TABLE)
            ->select([
                self::TABLE.'.id',
                self::TABLE.'.name',
                self::TABLE.'.slug',
                self::TABLE.'.is_active',
            ])
            ->whereIn(self::TABLE.'.id', $filteredSkills)
            ->orderBy(self::TABLE.'.name');
    }

    private function attachProfessionalFields(
        Collection $skills,
        bool $includeInactive,
    ): void {
        if ($skills->isEmpty()) {
            return;
        }

        $professionalFields = DB::table(self::PIVOT_TABLE)
            ->join(
                'professional_fields',
                'professional_fields.id',
                '=',
                self::PIVOT_TABLE.'.professional_field_id',
            )
            ->leftJoin(
                'professional_field_translations as professional_field_translation_en',
                function ($join): void {
                    $join
                        ->on(
                            'professional_field_translation_en.professional_field_id',
                            '=',
                            'professional_fields.id',
                        )
                        ->where('professional_field_translation_en.locale', 'en');
                },
            )
            ->leftJoin(
                'professional_field_translations as professional_field_translation_ar',
                function ($join): void {
                    $join
                        ->on(
                            'professional_field_translation_ar.professional_field_id',
                            '=',
                            'professional_fields.id',
                        )
                        ->where('professional_field_translation_ar.locale', 'ar');
                },
            )
            ->whereIn(
                self::PIVOT_TABLE.'.'.self::PIVOT_FOREIGN_KEY,
                $skills->pluck('id'),
            )
            ->when(
                ! $includeInactive,
                fn (QueryBuilder $query) => $query->where(
                    'professional_fields.is_active',
                    true,
                ),
            )
            ->orderBy(
                app()->getLocale() === 'ar'
                    ? 'professional_field_translation_ar.name'
                    : 'professional_field_translation_en.name',
            )
            ->select([
                self::PIVOT_TABLE.'.'.self::PIVOT_FOREIGN_KEY.' as record_id',
                'professional_fields.id',
                DB::raw(
                    app()->getLocale() === 'ar'
                        ? 'COALESCE(professional_field_translation_ar.name, professional_field_translation_en.name) as name'
                        : 'professional_field_translation_en.name as name',
                ),
                'professional_field_translation_en.name as name_en',
                'professional_field_translation_ar.name as name_ar',
                'professional_fields.slug',
                'professional_fields.is_active',
            ])
            ->get()
            ->groupBy('record_id');

        $skills->each(function (object $skill) use ($professionalFields): void {
            $skill->professional_fields = $professionalFields
                ->get($skill->id, collect())
                ->values();
        });
    }

    private function loadProfessionalFields(Skill $skill): Skill
    {
        $skill->load('professionalFields.translations');
        $skill->setRelation(
            'professionalFields',
            $skill->professionalFields
                ->sortBy(fn ($professionalField) => $professionalField->name)
                ->values(),
        );

        return $skill;
    }
}
