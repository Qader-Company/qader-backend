<?php

namespace App\Modules\V1\Clients\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientListSortEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientManagementActionEnum;
use App\Modules\V1\HiringRequests\Application\Services\HiringItemControlWorkflow;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminClientWorkspaceService
{
    private const LIST_RELATIONS = ['industries.translations', 'professionalFields.translations', 'media'];

    private const DETAIL_RELATIONS = [
        'owner.user', 'industries.translations', 'professionalFields.translations',
        'country.translations', 'city.translations', 'socialLinks', 'media',
    ];

    public function __construct(
        private readonly ClientManagementRecorder $recorder,
        private readonly ClientKpiService $kpis,
        private readonly HiringItemControlWorkflow $hiringControl,
    ) {}

    public function paginate(int $clientId, array $filters, User $actor): LengthAwarePaginator
    {
        $client = $this->client($clientId);
        $query = $client->ownedWorkspaces()
            ->with(self::LIST_RELATIONS)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['industry_id'] ?? null, fn (Builder $query, int $id) => $query->whereHas('industries', fn (Builder $industry) => $industry->whereKey($id)))
            ->when($filters['professional_field_id'] ?? null, fn (Builder $query, int $id) => $query->whereHas('professionalFields', fn (Builder $field) => $field->whereKey($id)));

        match (ClientListSortEnum::tryFrom((string) ($filters['sort'] ?? '')) ?? ClientListSortEnum::NEWEST) {
            ClientListSortEnum::NEWEST => $query->latest()->latest('id'),
            ClientListSortEnum::OLDEST => $query->oldest()->oldest('id'),
            ClientListSortEnum::NAME_ASC => $query->orderBy('name')->orderBy('id'),
            ClientListSortEnum::NAME_DESC => $query->orderByDesc('name')->orderByDesc('id'),
        };

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (Workspace $workspace) => $workspace->setAttribute('available_actions', $this->availableActions($workspace, $actor)));

        return $paginator;
    }

    public function show(int $clientId, int $workspaceId, User $actor): Workspace
    {
        $workspace = $this->workspace($clientId, $workspaceId)->load(self::DETAIL_RELATIONS);
        $workspace->setAttribute('kpis', $this->kpis->forWorkspace($workspace));
        $workspace->setAttribute('available_actions', $this->availableActions($workspace, $actor));

        return $workspace;
    }

    public function activate(int $clientId, int $workspaceId, User $actor): Workspace
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $workspaceId, $actor, $admin): void {
            $workspace = $this->workspace($clientId, $workspaceId, lock: true);
            if ($workspace->isActive()) {
                throw new ConflictHttpException(__('clients.workspace_already_active'));
            }

            $workspace->update(['status' => WorkspaceStatusEnum::ACTIVE]);
            $this->hiringControl->resumeAfterWorkspaceActivation($actor, $workspace);
            $this->recorder->record($admin, ClientManagementActionEnum::WORKSPACE_ACTIVATED, $clientId, $workspace->id);
        });

        return $this->show($clientId, $workspaceId, $actor);
    }

    public function deactivate(int $clientId, int $workspaceId, User $actor): Workspace
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $workspaceId, $actor, $admin): void {
            $workspace = $this->workspace($clientId, $workspaceId, lock: true);
            if (! $workspace->isActive()) {
                throw new ConflictHttpException(__('clients.workspace_already_inactive'));
            }

            $workspace->update(['status' => WorkspaceStatusEnum::INACTIVE]);
            $this->hiringControl->holdForWorkspaceDeactivation($actor, $workspace);
            $this->recorder->record($admin, ClientManagementActionEnum::WORKSPACE_DEACTIVATED, $clientId, $workspace->id);
        });

        return $this->show($clientId, $workspaceId, $actor);
    }

    public function delete(int $clientId, int $workspaceId, ?string $reason, User $actor): void
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $workspaceId, $reason, $actor, $admin): void {
            $this->softDelete($this->workspace($clientId, $workspaceId, lock: true), $actor, $admin, $reason);
        });
    }

    /** Soft delete pauses the workspace exactly like deactivation, then hides it from every list. */
    public function softDelete(Workspace $workspace, User $actor, Admin $admin, ?string $reason): void
    {
        $this->hiringControl->holdForWorkspaceDeactivation($actor, $workspace);
        $workspace->delete();
        $this->recorder->record($admin, ClientManagementActionEnum::WORKSPACE_DELETED, $workspace->owner_client_id, $workspace->id, reason: $reason);
    }

    public function members(int $clientId, int $workspaceId, array $filters, User $actor): LengthAwarePaginator
    {
        $workspace = $this->workspace($clientId, $workspaceId);
        $paginator = $workspace->members()
            ->with(['client.user', 'roles'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->whereHas('client.user', fn (Builder $user) => $user->where(fn (Builder $user) => $user
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['role_id'] ?? null, fn (Builder $query, int $roleId) => $query->whereHas('roles', fn (Builder $role) => $role->whereKey($roleId)))
            ->latest('joined_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        $canDelete = $actor->can(AdminPermissionEnum::CLIENTS_DELETE->value);
        $paginator->getCollection()->each(fn (WorkspaceMember $member) => $member->setAttribute(
            'available_actions',
            $canDelete && ! $this->isOwner($member) ? ['delete'] : [],
        ));

        return $paginator;
    }

    public function deleteMember(int $clientId, int $workspaceId, int $memberId, User $actor): void
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $workspaceId, $memberId, $admin): void {
            $workspace = $this->workspace($clientId, $workspaceId);
            $member = $workspace->members()->with('roles')->lockForUpdate()->findOrFail($memberId);
            if ($this->isOwner($member)) {
                throw new ConflictHttpException(__('clients.owner_cannot_be_deleted'));
            }

            $member->delete();
            $this->recorder->record($admin, ClientManagementActionEnum::WORKSPACE_MEMBER_DELETED, $clientId, $workspace->id, $member->id);
        });
    }

    public function taxCard(int $clientId, int $workspaceId): Media
    {
        return $this->workspace($clientId, $workspaceId)->getFirstMedia(Workspace::TAX_CARD_COLLECTION)
            ?? throw (new ModelNotFoundException)->setModel(Media::class);
    }

    /** @return array<int, string> */
    public function availableActions(Workspace $workspace, User $actor): array
    {
        $actions = [];
        if ($actor->can(AdminPermissionEnum::CLIENTS_MANAGE->value)) {
            $actions[] = $workspace->isActive() ? 'deactivate' : 'activate';
        }
        if ($actor->can(AdminPermissionEnum::CLIENTS_DELETE->value)) {
            $actions[] = 'delete';
        }

        return $actions;
    }

    private function client(int $clientId): Client
    {
        return Client::query()->findOrFail($clientId);
    }

    /** Nested routes only resolve workspaces owned by the given (non-deleted) client. */
    private function workspace(int $clientId, int $workspaceId, bool $lock = false): Workspace
    {
        return Workspace::query()
            ->where('owner_client_id', $this->client($clientId)->id)
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->findOrFail($workspaceId);
    }

    private function isOwner(WorkspaceMember $member): bool
    {
        return $member->roles->contains('key', 'owner');
    }
}
