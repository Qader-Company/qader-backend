<?php

namespace App\Modules\V1\Tools\Infrastructure\Persistence;

use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Tools\Domain\Repositories\ToolRepositoryInterface;
use App\Modules\V1\Tools\Infrastructure\Filters\ToolFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentToolRepository implements ToolRepositoryInterface
{
    private const TABLE = 'tools';

    private const PIVOT_TABLE = 'professional_field_tool';

    private const PIVOT_FOREIGN_KEY = 'tool_id';

    public function paginate(array $filters): LengthAwarePaginator
    {
        $tools = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: true,
        )->paginate($filters['per_page'] ?? 20);

        $this->attachProfessionalFields(
            tools: $tools->getCollection(),
            includeInactive: true,
        );

        return $tools;
    }

    public function listActive(array $filters): Collection
    {
        $tools = $this->buildListQuery(
            filters: $filters,
            includeInactiveProfessionalFields: false,
        )
            ->where(self::TABLE.'.is_active', true)
            ->get();

        $this->attachProfessionalFields(
            tools: $tools,
            includeInactive: false,
        );

        return $tools;
    }

    public function create(array $attributes): Tool
    {
        $professionalFieldIds = Arr::pull(
            $attributes,
            'professional_field_ids',
            [],
        );

        return DB::transaction(function () use (
            $attributes,
            $professionalFieldIds,
        ): Tool {
            $tool = Tool::query()->create($attributes);
            $tool->professionalFields()->sync($professionalFieldIds);

            return $this->loadProfessionalFields($tool);
        });
    }

    public function findOrFail(int $id): Tool
    {
        return Tool::query()->findOrFail($id);
    }

    public function update(Tool $tool, array $attributes): Tool
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
            $tool,
            $attributes,
            $shouldSyncProfessionalFields,
            $professionalFieldIds,
        ): Tool {
            $tool->update($attributes);

            if ($shouldSyncProfessionalFields) {
                $tool->professionalFields()->sync($professionalFieldIds);
            }

            return $this->loadProfessionalFields($tool->refresh());
        });
    }

    private function buildListQuery(
        array $filters,
        bool $includeInactiveProfessionalFields,
    ): QueryBuilder {
        $filters['only_active_professional_fields'] =
            ! $includeInactiveProfessionalFields;

        $filteredTools = Tool::filter($filters, ToolFilter::class)
            ->select(self::TABLE.'.id');

        return DB::table(self::TABLE)
            ->select([
                self::TABLE.'.id',
                self::TABLE.'.name',
                self::TABLE.'.slug',
                self::TABLE.'.is_active',
            ])
            ->whereIn(self::TABLE.'.id', $filteredTools)
            ->orderBy(self::TABLE.'.name');
    }

    private function attachProfessionalFields(
        Collection $tools,
        bool $includeInactive,
    ): void {
        if ($tools->isEmpty()) {
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
                $tools->pluck('id'),
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

        $tools->each(function (object $tool) use ($professionalFields): void {
            $tool->professional_fields = $professionalFields
                ->get($tool->id, collect())
                ->values();
        });
    }

    private function loadProfessionalFields(Tool $tool): Tool
    {
        $tool->load('professionalFields.translations');
        $tool->setRelation(
            'professionalFields',
            $tool->professionalFields
                ->sortBy(fn ($professionalField) => $professionalField->name)
                ->values(),
        );

        return $tool;
    }
}
