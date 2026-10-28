<?php

namespace App\Modules\V1\TalentMarketplace\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Requests\ListTalentMarketplaceRequest;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceTalentSelection;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TalentMarketplaceService
{
    private const CARD_RELATIONS = [
        'jobTitle',
        'professionalField.translations',
        'residenceCountry.translations',
        'residenceCity.translations',
        'expectedCurrency.translations',
        'skills',
        'workTypes',
        'workModes',
    ];

    private const DETAIL_RELATIONS = [
        ...self::CARD_RELATIONS,
        'industries.translations',
        'tools',
        'languages.translations',
        'experiences',
        'projects.media',
    ];

    public function __construct(private readonly WorkspaceAccessService $access) {}

    /** @return array{paginator: LengthAwarePaginator, selected_talents_count: int} */
    public function list(User $user, Workspace $workspace, array $filters): array
    {
        $member = $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::MARKETPLACE_VIEW->value,
        );
        $canManage = $this->access->allows(
            $member,
            ClientPermissionEnum::TALENT_SELECTIONS_MANAGE->value,
        );

        $query = $this->availableQuery($workspace)->with(self::CARD_RELATIONS);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $workspace, $filters['sort'] ?? 'highest_match');

        $paginator = $query
            ->paginate((int) ($filters['per_page'] ?? 12))
            ->withQueryString();
        $this->decorate($paginator->getCollection(), $canManage);

        return [
            'paginator' => $paginator,
            'selected_talents_count' => $this->selectedQuery($workspace)->count(),
        ];
    }

    public function details(User $user, Workspace $workspace, Talent $talent): Talent
    {
        $member = $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::MARKETPLACE_VIEW->value,
        );
        $canManage = $this->access->allows(
            $member,
            ClientPermissionEnum::TALENT_SELECTIONS_MANAGE->value,
        );

        $talent = $this->availableQuery($workspace)
            ->with(self::DETAIL_RELATIONS)
            ->findOrFail($talent->getKey());
        $this->decorate(collect([$talent]), $canManage);

        return $talent;
    }

    public function filters(User $user, Workspace $workspace): array
    {
        $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::MARKETPLACE_VIEW->value,
        );

        return [
            'job_titles' => JobTitle::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (JobTitle $item): array => ['id' => $item->id, 'name' => $item->name])->values(),
            'seniority_levels' => EnumOptions::make(SeniorityLevelEnum::class, 'seniority_levels'),
            'currencies' => Currency::query()->where('is_active', true)->with('translations')->orderBy('code')->get()
                ->map(fn (Currency $item): array => ['id' => $item->id, 'code' => $item->code, 'symbol' => $item->symbol, 'name' => $item->name])->values(),
            'skills' => Skill::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Skill $item): array => ['id' => $item->id, 'name' => $item->name])->values(),
            'tools' => Tool::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Tool $item): array => ['id' => $item->id, 'name' => $item->name])->values(),
            'work_types' => EnumOptions::make(WorkTypeEnum::class, 'work_types'),
            'work_modes' => EnumOptions::make(WorkModeEnum::class, 'work_modes'),
            'countries' => Country::query()->where('is_active', true)->with('translations')->orderBy('code')->get()
                ->map(fn (Country $item): array => ['id' => $item->id, 'code' => $item->code, 'name' => $item->name])->values(),
            'cities' => City::query()->where('is_active', true)->with('translations')->orderBy('country_id')->orderBy('id')->get()
                ->map(fn (City $item): array => ['id' => $item->id, 'country_id' => $item->country_id, 'name' => $item->name])->values(),
            'languages' => Language::query()->where('is_active', true)->with('translations')->orderBy('code')->get()
                ->map(fn (Language $item): array => ['id' => $item->id, 'code' => $item->code, 'name' => $item->name])->values(),
            'language_proficiency_levels' => EnumOptions::make(LanguageProficiencyEnum::class, 'language_proficiencies'),
            'sort_options' => collect(ListTalentMarketplaceRequest::SORTS)
                ->map(fn (string $sort): array => ['value' => $sort, 'label' => __("marketplace.sorts.{$sort}")])
                ->values(),
        ];
    }

    /** @return array{paginator: LengthAwarePaginator} */
    public function selections(User $user, Workspace $workspace, int $perPage = 12): array
    {
        $member = $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::TALENT_SELECTIONS_VIEW->value,
        );
        $canManage = $this->access->allows(
            $member,
            ClientPermissionEnum::TALENT_SELECTIONS_MANAGE->value,
        );
        $paginator = $this->selectedQuery($workspace)
            ->with(self::CARD_RELATIONS)
            ->latest('workspace_talent_selections.created_at')
            ->paginate($perPage);
        $this->decorate($paginator->getCollection(), $canManage);

        return ['paginator' => $paginator];
    }

    public function addSelection(User $user, Workspace $workspace, int $talentId): Talent
    {
        $member = $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::TALENT_SELECTIONS_MANAGE->value,
        );
        $talent = $this->availableQuery($workspace)
            ->with(self::CARD_RELATIONS)
            ->findOrFail($talentId);

        $selection = WorkspaceTalentSelection::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'talent_id' => $talent->id],
            ['added_by_member_id' => $member->id],
        );

        if (! $selection->wasRecentlyCreated) {
            throw ValidationException::withMessages([
                'talent_id' => [__('marketplace.already_selected')],
            ]);
        }

        $talent->setAttribute('is_selected', true);
        $this->decorate(collect([$talent]), true);

        return $talent;
    }

    public function removeSelection(User $user, Workspace $workspace, Talent $talent): void
    {
        $this->access->authorize(
            $user,
            $workspace,
            ClientPermissionEnum::TALENT_SELECTIONS_MANAGE->value,
        );

        $selectionQuery = WorkspaceTalentSelection::query()
            ->where('workspace_id', $workspace->id)
            ->where('talent_id', $talent->id);

        if ($selectionQuery->delete() === 0) {
            throw (new ModelNotFoundException)->setModel(WorkspaceTalentSelection::class);
        }
    }

    private function availableQuery(Workspace $workspace): Builder
    {
        return Talent::query()
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('applications', fn (Builder $query) => $query
                ->where('status', TalentApplicationStatusEnum::ACCEPTED->value))
            ->whereDoesntHave('blacklistEntries', fn (Builder $query) => $query
                ->whereNull('removed_at'))
            ->withExists(['workspaceSelections as is_selected' => fn (Builder $query) => $query
                ->where('workspace_id', $workspace->id)]);
    }

    private function selectedQuery(Workspace $workspace): Builder
    {
        return $this->availableQuery($workspace)
            ->join('workspace_talent_selections', function ($join) use ($workspace): void {
                $join->on('workspace_talent_selections.talent_id', '=', 'talents.id')
                    ->where('workspace_talent_selections.workspace_id', '=', $workspace->id);
            })
            ->select('talents.*');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (($filters['search'] ?? '') !== '') {
            $search = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('talent_code', 'like', $search)
                    ->orWhere('professional_summary', 'like', $search)
                    ->orWhereHas('jobTitle', fn (Builder $relation) => $relation->where('name', 'like', $search))
                    ->orWhereHas('skills', fn (Builder $relation) => $relation->where('name', 'like', $search))
                    ->orWhereHas('tools', fn (Builder $relation) => $relation->where('name', 'like', $search))
                    ->orWhereHas('professionalField.translations', fn (Builder $relation) => $relation->where('name', 'like', $search));
            });
        }

        $this->whereValue($query, 'job_title_id', $filters);
        $this->whereValue($query, 'residence_country_id', $filters, 'country_id');
        $this->whereValue($query, 'residence_city_id', $filters, 'city_id');

        if (isset($filters['seniority_level'])) {
            $query->whereIn('seniority_level', $filters['seniority_level']);
        }
        if (isset($filters['years_of_experience_min'])) {
            $query->where('years_of_experience', '>=', $filters['years_of_experience_min']);
        }
        if (isset($filters['currency_id'])) {
            $query->where('expected_currency_id', $filters['currency_id']);
        }
        if (isset($filters['salary_min'])) {
            $query->where('expected_salary_max', '>=', $filters['salary_min']);
        }
        if (isset($filters['salary_max'])) {
            $query->where('expected_salary_min', '<=', $filters['salary_max']);
        }
        if (isset($filters['notice_period_days_max'])) {
            $query->where('notice_period_days', '<=', $filters['notice_period_days_max']);
        }

        $this->whereRelatedIds($query, 'skills', $filters['skill_ids'] ?? null);
        $this->whereRelatedIds($query, 'tools', $filters['tool_ids'] ?? null);

        if (isset($filters['work_types'])) {
            $query->whereHas('workTypes', fn (Builder $relation) => $relation
                ->whereIn('work_type', $filters['work_types']));
        }
        if (isset($filters['work_modes'])) {
            $query->whereHas('workModes', fn (Builder $relation) => $relation
                ->whereIn('work_mode', $filters['work_modes']));
        }
        if (isset($filters['language_id'])) {
            $query->whereHas('languages', fn (Builder $relation) => $relation
                ->where('languages.id', $filters['language_id'])
                ->where('talent_languages.proficiency_level', $filters['language_proficiency_level']));
        }
    }

    private function applySort(Builder $query, Workspace $workspace, string $sort): void
    {
        if ($sort === 'experience_high_to_low') {
            $query->orderByDesc('years_of_experience')->orderBy('talents.id');

            return;
        }
        if ($sort === 'salary_low_to_high') {
            $query->orderBy('expected_salary_min')->orderBy('talents.id');

            return;
        }
        if ($sort === 'salary_high_to_low') {
            $query->orderByDesc('expected_salary_max')->orderBy('talents.id');

            return;
        }

        $acceptedAt = TalentApplication::query()
            ->select('review_decided_at')
            ->whereColumn('talent_applications.talent_id', 'talents.id')
            ->where('status', TalentApplicationStatusEnum::ACCEPTED->value)
            ->limit(1);

        if ($sort === 'newest') {
            $query->orderByDesc($acceptedAt)->orderByDesc('talents.id');

            return;
        }

        $fieldIds = $workspace->professionalFields()->pluck('professional_fields.id')->all();
        if ($fieldIds !== []) {
            $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
            $query->orderByRaw(
                "case when professional_field_id in ({$placeholders}) then 1 else 0 end desc",
                $fieldIds,
            );
        }

        $industryIds = $workspace->industries()->pluck('industries.id')->all();
        if ($industryIds !== []) {
            $query->withCount(['industries as workspace_industry_matches' => fn (Builder $relation) => $relation
                ->whereIn('industries.id', $industryIds)])
                ->orderByDesc('workspace_industry_matches');
        }

        $query->orderByDesc($acceptedAt)->orderBy('talents.id');
    }

    private function whereValue(Builder $query, string $column, array $filters, ?string $key = null): void
    {
        $key ??= $column;
        if (isset($filters[$key])) {
            $query->where($column, $filters[$key]);
        }
    }

    private function whereRelatedIds(Builder $query, string $relation, ?array $ids): void
    {
        if ($ids !== null) {
            $query->whereHas($relation, fn (Builder $related) => $related->whereIn("{$relation}.id", $ids));
        }
    }

    private function decorate($talents, bool $canManage): void
    {
        $talents->each(function (Talent $talent) use ($canManage): void {
            $selected = (bool) ($talent->is_selected ?? false);
            $actions = ['view_profile'];

            if ($canManage) {
                $actions[] = $selected ? 'remove_from_squad' : 'add_to_squad';
            }

            $talent->setAttribute('available_actions', $actions);
        });
    }
}
