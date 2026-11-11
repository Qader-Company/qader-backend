<?php

namespace App\Modules\V1\Clients\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientAccountStatusEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientListSortEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientManagementActionEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientRegistrationPeriodEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminClientService
{
    public function __construct(
        private readonly ClientKpiService $kpis,
        private readonly ClientManagementRecorder $recorder,
        private readonly AdminClientWorkspaceService $workspaces,
    ) {}

    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $period = ClientRegistrationPeriodEnum::tryFrom((string) ($filters['registration_period'] ?? ''));
        $hasRange = isset($filters['registered_from']) || isset($filters['registered_to']);

        $query = Client::query()
            ->whereHas('user')
            ->with('user')
            ->withCount('ownedWorkspaces')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('phone_number', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->whereHas(
                'user',
                fn (Builder $user) => $user->where('is_active', $status === ClientAccountStatusEnum::ACTIVE->value),
            ))
            ->when($hasRange && isset($filters['registered_from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['registered_from']))
            ->when($hasRange && isset($filters['registered_to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['registered_to']))
            ->when(! $hasRange && $period !== null, fn (Builder $query) => $query->where('created_at', '>=', match ($period) {
                ClientRegistrationPeriodEnum::LAST_7_DAYS => now()->subDays(7),
                ClientRegistrationPeriodEnum::LAST_30_DAYS => now()->subDays(30),
                ClientRegistrationPeriodEnum::LAST_90_DAYS => now()->subDays(90),
                ClientRegistrationPeriodEnum::THIS_YEAR => now()->startOfYear(),
            }));

        $nameOrder = User::query()->select('first_name')->whereColumn('users.id', 'clients.user_id');
        match (ClientListSortEnum::tryFrom((string) ($filters['sort'] ?? '')) ?? ClientListSortEnum::NEWEST) {
            ClientListSortEnum::NEWEST => $query->latest()->latest('id'),
            ClientListSortEnum::OLDEST => $query->oldest()->oldest('id'),
            ClientListSortEnum::NAME_ASC => $query->orderBy($nameOrder)->orderBy('id'),
            ClientListSortEnum::NAME_DESC => $query->orderByDesc($nameOrder)->orderByDesc('id'),
        };

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (Client $client) => $client->setAttribute('available_actions', $this->availableActions($client, $actor)));

        return $paginator;
    }

    public function show(int $clientId, User $actor): Client
    {
        $client = Client::query()->whereHas('user')->with('user')->withCount('ownedWorkspaces')->findOrFail($clientId);
        $client->setAttribute('kpis', $this->kpis->forClient($client));
        $client->setAttribute('suspension', $client->user->is_active ? null : $this->latestSuspension($client));
        $client->setAttribute('available_actions', $this->availableActions($client, $actor));

        return $client;
    }

    public function suspend(int $clientId, string $reason, User $actor): Client
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $reason, $admin): void {
            $client = Client::query()->with('user')->lockForUpdate()->findOrFail($clientId);
            if (! $client->user->is_active) {
                throw new ConflictHttpException(__('clients.already_suspended'));
            }

            $client->user->forceFill(['is_active' => false])->save();
            $this->revokeAccess($client->user);
            $this->recorder->record($admin, ClientManagementActionEnum::CLIENT_SUSPENDED, $client->id, reason: $reason);
        });

        return $this->show($clientId, $actor);
    }

    public function reactivate(int $clientId, User $actor): Client
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $admin): void {
            $client = Client::query()->with('user')->lockForUpdate()->findOrFail($clientId);
            if ($client->user->is_active) {
                throw new ConflictHttpException(__('clients.already_active'));
            }

            $client->user->forceFill(['is_active' => true])->save();
            $this->recorder->record($admin, ClientManagementActionEnum::CLIENT_REACTIVATED, $client->id);
        });

        return $this->show($clientId, $actor);
    }

    /**
     * Soft delete: the client loses access, owned workspaces are soft deleted with them (pausing their hiring),
     * and all history is kept for the future deleted-items page.
     */
    public function delete(int $clientId, string $reason, User $actor): void
    {
        $admin = $this->recorder->actor($actor);

        DB::transaction(function () use ($clientId, $reason, $actor, $admin): void {
            $client = Client::query()->with('user')->lockForUpdate()->findOrFail($clientId);

            foreach ($client->ownedWorkspaces()->orderBy('id')->get() as $workspace) {
                $this->workspaces->softDelete($workspace, $actor, $admin, $reason);
            }

            $client->user?->forceFill(['is_active' => false])->save();
            if ($client->user !== null) {
                $this->revokeAccess($client->user);
            }
            $client->delete();
            $this->recorder->record($admin, ClientManagementActionEnum::CLIENT_DELETED, $client->id, reason: $reason);
        });
    }

    /** @return array<int, string> */
    public function availableActions(Client $client, User $actor): array
    {
        $actions = [];
        if ($actor->can(AdminPermissionEnum::CLIENTS_MANAGE->value)) {
            $actions[] = $client->user?->is_active ? 'suspend' : 'reactivate';
        }
        if ($actor->can(AdminPermissionEnum::CLIENTS_DELETE->value)) {
            $actions[] = 'delete';
        }

        return $actions;
    }

    private function revokeAccess(User $user): void
    {
        $user->tokens()->delete();
        $user->userDevices()->delete();
    }

    /** @return array{reason: ?string, suspended_at: mixed}|null */
    private function latestSuspension(Client $client): ?array
    {
        $event = $client->managementEvents()->where('action', ClientManagementActionEnum::CLIENT_SUSPENDED->value)->first();

        return $event === null ? null : ['reason' => $event->reason, 'suspended_at' => $event->created_at];
    }
}
