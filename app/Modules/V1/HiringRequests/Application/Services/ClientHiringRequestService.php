<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferPartyEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceTalentSelection;
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
        'items.contractType',
        'items.jobTitle',
        'items.latestOffer.currency.translations',
        'items.offers.currency.translations',
        'items.interview',
        'items.hiringRequest.workspace.media',
    ];

    public function __construct(
        private readonly WorkspaceAccessService $access,
        private readonly ContractConfigurationResolver $contracts,
    ) {}

    public function paginate(User $user, Workspace $workspace, array $filters): LengthAwarePaginator
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_VIEW->value);

        $paginator = HiringRequest::query()
            ->where('workspace_id', $workspace->id)
            ->with(['assignedAdmin.user'])
            ->withCount('items')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('reference', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (HiringRequest $request) => $request
            ->setAttribute('available_actions', ['view']));

        return $paginator;
    }

    public function submitDirect(User $user, Workspace $workspace, array $data): HiringRequest
    {
        $member = $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value);

        return DB::transaction(function () use ($user, $workspace, $member, $data): HiringRequest {
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            $talentIds = collect($data['items'])->pluck('talent_id')->all();
            WorkspaceTalentSelection::query()
                ->where('workspace_id', $workspace->id)
                ->whereIn('talent_id', $talentIds)
                ->lockForUpdate()
                ->get();
            $talents = $this->selectedEligibleTalents($workspace, $talentIds);
            $contractTypes = $this->activeContractTypes($data['items']);
            $submittedAt = now();
            $request = HiringRequest::query()->create([
                'workspace_id' => $workspace->id,
                'created_by_member_id' => $member->id,
                'status' => HiringRequestStatusEnum::PENDING,
                'submitted_at' => $submittedAt,
            ]);
            $this->ensureNoActiveRequests($workspace, $request, $talentIds);

            foreach ($data['items'] as $dataItem) {
                $talent = $talents->get($dataItem['talent_id']);
                $contractType = $contractTypes->get($dataItem['contract_type']);
                $resolved = $this->contracts->resolve($contractType, $talent, $workspace, (float) $dataItem['salary_amount']);
                $item = $request->items()->create([
                    'talent_id' => $talent->id,
                    'job_title_id' => $talent->job_title_id,
                    'contract_type' => $dataItem['contract_type'],
                    'contract_type_id' => $resolved['contract_type_id'],
                    'contract_configuration_snapshot' => $resolved['snapshot'],
                    'interview_required' => $dataItem['interview_required'],
                    'status' => HiringRequestItemStatusEnum::PENDING,
                ]);
                $item->offers()->create([
                    ...$resolved['offer'],
                    'proposed_by' => HiringOfferPartyEnum::CLIENT,
                    'status' => HiringOfferStatusEnum::PENDING,
                ]);
            }

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

    public function show(User $user, Workspace $workspace, int $requestId): HiringRequest
    {
        $this->access->authorize($user, $workspace, ClientPermissionEnum::HIRING_REQUESTS_VIEW->value);
        $request = $this->findInWorkspace($workspace, $requestId);
        $request->setAttribute('available_actions', ['view']);

        return $request;
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
            ->with(['jobTitle', 'expectedCurrency', 'professionalField'])
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

    private function activeContractTypes(array $items)
    {
        $types = collect($items)->pluck('contract_type')->unique()->values();
        $contractTypes = ContractType::query()
            ->where('is_active', true)
            ->whereIn('type', $types)
            ->with(['benefits', 'terms', 'feeOverrides'])
            ->get()
            ->keyBy(fn (ContractType $contractType): string => $contractType->type->value);

        if ($contractTypes->count() !== $types->count()) {
            throw ValidationException::withMessages(['items' => [__('hiring.contract_type_inactive')]]);
        }

        return $contractTypes;
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

    private function findInWorkspace(Workspace $workspace, int $requestId): HiringRequest
    {
        return HiringRequest::query()->where('workspace_id', $workspace->id)->with(self::RELATIONS)->findOrFail($requestId);
    }

    private function load(HiringRequest $request): HiringRequest
    {
        return $request->refresh()->load(self::RELATIONS);
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
