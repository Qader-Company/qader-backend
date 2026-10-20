<?php

namespace App\Modules\V1\JobTitles\Infrastructure\Persistence;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\JobTitles\Domain\Repositories\JobTitleRepositoryInterface;
use App\Modules\V1\JobTitles\Infrastructure\Filters\JobTitleFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentJobTitleRepository implements JobTitleRepositoryInterface
{
    private const TABLE = 'job_titles';

    private const PIVOT_TABLE = 'job_title_professional_field';

    private const PIVOT_FOREIGN_KEY = 'job_title_id';

    public function paginate(array $filters): LengthAwarePaginator
    {
        $jobTitles = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: true,
        )->paginate($filters['per_page'] ?? 20);

        $this->attachProfessionalFields(
            jobTitles: $jobTitles->getCollection(),
            includeInactive: true,
        );

        return $jobTitles;
    }

    public function listActive(array $filters): Collection
    {
        $jobTitles = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: false,
        )
            ->where(self::TABLE.'.is_active', true)
            ->get();

        $this->attachProfessionalFields(
            jobTitles: $jobTitles,
            includeInactive: false,
        );

        return $jobTitles;
    }

    public function create(array $attributes): JobTitle
    {
        $professionalFieldIds = Arr::pull(
            $attributes,
            'professional_field_ids',
            [],
        );

        return DB::transaction(function () use (
            $attributes,
            $professionalFieldIds,
        ): JobTitle {
            $jobTitle = JobTitle::query()->create($attributes);
            $jobTitle->professionalFields()->sync($professionalFieldIds);

            return $this->loadProfessionalFields($jobTitle);
        });
    }

    public function findOrFail(int $id): JobTitle
    {
        return JobTitle::query()->findOrFail($id);
    }

    public function update(JobTitle $jobTitle, array $attributes): JobTitle
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
            $jobTitle,
            $attributes,
            $shouldSyncProfessionalFields,
            $professionalFieldIds,
        ): JobTitle {
            $jobTitle->update($attributes);

            if ($shouldSyncProfessionalFields) {
                $jobTitle->professionalFields()->sync($professionalFieldIds);
            }

            return $this->loadProfessionalFields($jobTitle->refresh());
        });
    }

    private function buildListQuery(
        array $filters,
        bool $includeInactiveProfessionalFields,
    ): QueryBuilder {
        $filters['only_active_professional_fields'] =
            ! $includeInactiveProfessionalFields;

        $filteredJobTitles = JobTitle::filter($filters, JobTitleFilter::class)
            ->select(self::TABLE.'.id');

        return DB::table(self::TABLE)
            ->select([
                self::TABLE.'.id',
                self::TABLE.'.name',
                self::TABLE.'.slug',
                self::TABLE.'.is_active',
            ])
            ->whereIn(self::TABLE.'.id', $filteredJobTitles)
            ->orderBy(self::TABLE.'.name');
    }

    private function attachProfessionalFields(Collection $jobTitles, bool $includeInactive): void
    {
        if ($jobTitles->isEmpty()) {
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
                $jobTitles->pluck('id'),
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
                DB::raw(
                    app()->getLocale() === 'ar'
                        ? 'COALESCE(professional_field_translation_ar.description, professional_field_translation_en.description) as description'
                        : 'professional_field_translation_en.description as description',
                ),
                'professional_field_translation_en.name as name_en',
                'professional_field_translation_ar.name as name_ar',
                'professional_field_translation_en.description as description_en',
                'professional_field_translation_ar.description as description_ar',
                'professional_fields.slug',
                'professional_fields.is_active',
            ])
            ->get()
            ->groupBy('record_id');

        $jobTitles->each(function (object $jobTitle) use ($professionalFields): void {
            $jobTitle->professional_fields = $professionalFields->get($jobTitle->id, collect())->values();
        });
    }

    private function loadProfessionalFields(JobTitle $jobTitle): JobTitle
    {
        $jobTitle->load('professionalFields.translations');
        $jobTitle->setRelation(
            'professionalFields',
            $jobTitle->professionalFields
                ->sortBy(fn ($professionalField) => $professionalField->name)
                ->values(),
        );

        return $jobTitle;
    }
}
