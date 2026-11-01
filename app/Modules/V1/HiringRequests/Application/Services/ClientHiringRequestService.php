<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceTalentSelection;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientHiringRequestService
{
    private const RELATIONS = [
        'workspace',
        'assignedAdmin.user',
        'items.talent.jobTitle',
        'items.jobTitle',
        'items.latestOffer.currency.translations',
    ];

    public function __construct(private readonly WorkspaceAccessService $access) {}

    public function options(User $user, Workspace $workspace): array
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_VIEW->value);

        return [
            'contract_types' => EnumOptions::make(HiringContractTypeEnum::class, 'hiring_contract_types'),
            'work_types' => EnumOptions::make(WorkTypeEnum::class, 'work_types'),
            'work_modes' => EnumOptions::make(WorkModeEnum::class, 'work_modes'),
            'work_days' => EnumOptions::make(WorkDayEnum::class, 'work_days'),
            'currencies' => Currency::query()->where('is_active', true)->with('translations')->orderBy('code')->get()
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'name' => $currency->name,
                ])->values(),
        ];
    }

    public function paginate(User $user, Workspace $workspace, array $filters): LengthAwarePaginator
    {
        $member = $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_VIEW->value);

        $paginator = HiringRequest::query()
            ->where('workspace_id', $workspace->id)
            ->with(['assignedAdmin.user'])
            ->withCount('items')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('reference', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (HiringRequest $request) => $request
            ->setAttribute('available_actions', $this->clientActions($request, $member)));

        return $paginator;
    }

    public function createDraft(User $user, Workspace $workspace, array $data): HiringRequest
    {
        $member = $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value);

        return DB::transaction(function () use ($user, $workspace, $member, $data): HiringRequest {
            $talents = $this->selectedEligibleTalents($workspace, collect($data['items'])->pluck('talent_id')->all());
            $request = HiringRequest::query()->create([
                'workspace_id' => $workspace->id,
                'created_by_member_id' => $member->id,
                'status' => HiringRequestStatusEnum::DRAFT,
            ]);

            $this->replaceItems($request, $data['items'], $talents);
            $this->event($request, $user, 'created');

            $result = $this->load($request);
            $result->setAttribute('available_actions', $this->clientActions($result, $member));

            return $result;
        });
    }

    public function show(User $user, Workspace $workspace, int $requestId): HiringRequest
    {
        $member = $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_VIEW->value);
        $request = $this->findInWorkspace($workspace, $requestId);
        $request->setAttribute('available_actions', $this->clientActions($request, $member));

        return $request;
    }

    public function updateDraft(User $user, Workspace $workspace, int $requestId, array $data): HiringRequest
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value);

        $member = $this->access->member($user, $workspace);

        return DB::transaction(function () use ($user, $workspace, $requestId, $data, $member): HiringRequest {
            $request = HiringRequest::query()->where('workspace_id', $workspace->id)->lockForUpdate()->findOrFail($requestId);
            $this->ensureDraft($request);
            $talents = $this->selectedEligibleTalents($workspace, collect($data['items'])->pluck('talent_id')->all());

            $request->items()->delete();
            $this->replaceItems($request, $data['items'], $talents);
            $this->event($request, $user, 'updated');

            $result = $this->load($request);
            $result->setAttribute('available_actions', $this->clientActions($result, $member));

            return $result;
        });
    }

    public function submit(User $user, Workspace $workspace, int $requestId): HiringRequest
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value);

        return DB::transaction(function () use ($user, $workspace, $requestId): HiringRequest {
            // Serialize submissions inside the same workspace so two concurrent
            // requests cannot reserve the same talent at the same time.
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            $request = HiringRequest::query()
                ->where('workspace_id', $workspace->id)
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($requestId);
            $this->ensureDraft($request);

            $talentIds = $request->items->pluck('talent_id')->all();
            WorkspaceTalentSelection::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('talent_id', $talentIds)
                ->lockForUpdate()
                ->get();
            $this->selectedEligibleTalents($workspace, $talentIds);
            $this->ensureNoActiveRequests($workspace, $request, $talentIds);

            $submittedAt = now();
            $request->update([
                'status' => HiringRequestStatusEnum::PENDING,
                'submitted_at' => $submittedAt,
            ]);
            $request->items()->update(['status' => HiringRequestItemStatusEnum::PENDING->value]);
            WorkspaceTalentSelection::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('talent_id', $talentIds)
                ->delete();
            $this->event($request, $user, 'submitted', ['submitted_at' => $submittedAt]);

            $result = $this->load($request);
            $result->setAttribute('available_actions', ['view']);

            return $result;
        });
    }

    private function selectedEligibleTalents(Workspace $workspace, array $talentIds)
    {
        $selectedCount = WorkspaceTalentSelection::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('talent_id', $talentIds)
            ->count();
        if ($selectedCount !== count(array_unique($talentIds))) {
            throw ValidationException::withMessages(['items' => [__('hiring.talent_not_selected')]]);
        }

        $talents = Talent::query()
            ->whereIn('id', $talentIds)
            ->whereNotNull('job_title_id')
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('applications', fn (Builder $query) => $query->where('status', TalentApplicationStatusEnum::ACCEPTED->value))
            ->whereDoesntHave('blacklistEntries', fn (Builder $query) => $query->whereNull('removed_at'))
            ->get()
            ->keyBy('id');
        if ($talents->count() !== count(array_unique($talentIds))) {
            throw ValidationException::withMessages(['items' => [__('hiring.talent_not_eligible')]]);
        }

        return $talents;
    }

    private function replaceItems(HiringRequest $request, array $items, $talents): void
    {
        foreach ($items as $data) {
            $talent = $talents->get($data['talent_id']);
            $item = $request->items()->create([
                'talent_id' => $talent->id,
                'job_title_id' => $talent->job_title_id,
                'contract_type' => $data['contract_type'],
                'interview_required' => $data['interview_required'],
                'status' => HiringRequestItemStatusEnum::DRAFT,
            ]);
            $item->offers()->create([
                ...$data['offer'],
                'hybrid_work_days' => $data['offer']['hybrid_work_days'] ?? null,
                'status' => HiringOfferStatusEnum::PENDING,
            ]);
        }
    }

    private function ensureNoActiveRequests(Workspace $workspace, HiringRequest $request, array $talentIds): void
    {
        $exists = HiringRequestItem::query()
            ->whereIn('talent_id', $talentIds)
            ->where('hiring_request_id', '!=', $request->id)
            ->whereHas('hiringRequest', fn (Builder $query) => $query
                ->where('workspace_id', $workspace->id)
                ->whereIn('status', [HiringRequestStatusEnum::PENDING->value, HiringRequestStatusEnum::ACTIVE->value]))
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['items' => [__('hiring.talent_has_active_request')]]);
        }
    }

    private function ensureDraft(HiringRequest $request): void
    {
        if ($request->status !== HiringRequestStatusEnum::DRAFT) {
            throw ValidationException::withMessages(['hiring_request' => [__('hiring.draft_only')]]);
        }
    }

    private function findInWorkspace(Workspace $workspace, int $requestId): HiringRequest
    {
        return HiringRequest::query()->where('workspace_id', $workspace->id)->with(self::RELATIONS)->findOrFail($requestId);
    }

    private function load(HiringRequest $request): HiringRequest
    {
        return $request->refresh()->load(self::RELATIONS);
    }

    private function clientActions(HiringRequest $request, $member): array
    {
        $actions = ['view'];
        if ($request->status === HiringRequestStatusEnum::DRAFT && $this->access->allows($member, ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value)) {
            $actions[] = 'update';
            $actions[] = 'submit';
        }

        return $actions;
    }

    private function event(HiringRequest $request, User $actor, string $action, array $data = []): void
    {
        $request->events()->create([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'data' => $data === [] ? null : $data,
            'created_at' => now(),
        ]);
    }
}
