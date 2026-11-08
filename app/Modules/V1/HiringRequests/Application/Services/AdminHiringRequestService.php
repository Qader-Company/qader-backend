<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestListScopeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminHiringRequestService
{
    private const RELATIONS = [
        'workspace.owner.user',
        'createdByMember.client.user',
        'assignedAdmin.user',
        'items.talent.user',
        'items.talent.jobTitle',
        'items.jobTitle',
        'items.latestOffer.currency.translations',
        'items.offers.currency.translations',
        'items.interview',
        'items.hiringRequest.workspace.media',
        'events.actor',
    ];

    public function paginate(array $filters, Admin $actor): LengthAwarePaginator
    {
        $paginator = HiringRequest::query()
            ->with(['workspace', 'assignedAdmin.user'])
            ->withCount('items')
            ->when(($filters['scope'] ?? HiringRequestListScopeEnum::ALL->value) === HiringRequestListScopeEnum::MINE->value, fn (Builder $query) => $query->where('assigned_admin_id', $actor->id))
            ->when($filters['status'] ?? null, fn (Builder $query, array $statuses) => $query->whereIn('status', $statuses))
            ->when($filters['assigned_admin_id'] ?? null, fn (Builder $query, int $adminId) => $query->where('assigned_admin_id', $adminId))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('reference', 'like', $like)
                        ->orWhereHas('workspace', fn (Builder $workspace) => $workspace->where('name', 'like', $like))
                        ->orWhereHas('workspace.owner.user', fn (Builder $user) => $user
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like));
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
        $paginator->getCollection()->each(fn (HiringRequest $request) => $request
            ->setAttribute('available_actions', $this->availableActions($request, $actor->user)));

        return $paginator;
    }

    public function show(int $requestId, User $user): HiringRequest
    {
        $request = $this->load($requestId);
        $request->setAttribute('available_actions', $this->availableActions($request, $user));

        return $request;
    }

    public function assignees()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('admin')
            ->permission(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->value)
            ->with('admin')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->admin->id,
                'full_name' => trim($user->first_name.' '.$user->last_name),
                'email' => $user->email,
            ])->values();
    }

    public function assign(int $requestId, int $adminId, User $actor): HiringRequest
    {
        return $this->changeAssignee($requestId, $adminId, $actor, false);
    }

    public function reassign(int $requestId, int $adminId, User $actor): HiringRequest
    {
        return $this->changeAssignee($requestId, $adminId, $actor, true);
    }

    public function start(int $requestId, User $user, Admin $actor): HiringRequest
    {
        return DB::transaction(function () use ($requestId, $user, $actor): HiringRequest {
            $request = HiringRequest::query()->with(['items.latestOffer'])->lockForUpdate()->findOrFail($requestId);
            if ($request->status !== HiringRequestStatusEnum::PENDING) {
                throw ValidationException::withMessages(['hiring_request' => [__('hiring.pending_only')]]);
            }
            if ($request->assigned_admin_id !== null && (int) $request->assigned_admin_id !== (int) $actor->id) {
                throw ValidationException::withMessages(['hiring_request' => [__('hiring.responsible_admin_only')]]);
            }

            $previousAdminId = $request->assigned_admin_id;
            $sentAt = now();
            $duration = app(HiringRuntimeSettings::class)->offerResponseHours();
            $dueAt = $sentAt->copy()->addHours($duration);
            $request->update([
                'assigned_admin_id' => $actor->id,
                'status' => HiringRequestStatusEnum::ACTIVE,
                'started_at' => $sentAt,
            ]);

            foreach ($request->items as $item) {
                $item->update(['status' => HiringRequestItemStatusEnum::AWAITING_TALENT_RESPONSE]);
                $offer = $item->latestOffer;
                if ($offer === null || $offer->sent_at !== null) {
                    continue;
                }
                $item->loadMissing(['talent', 'hiringRequest.workspace.city.translations', 'hiringRequest.workspace.country.translations']);
                $workspace = $item->hiringRequest->workspace;
                $offer->forceFill([
                    'notice_period_days' => $offer->notice_period_days ?? $item->talent->notice_period_days,
                    'work_location' => $offer->work_location ?? (collect([$workspace->city?->name, $workspace->country?->name])->filter()->implode(', ') ?: null),
                ]);
                $offer->update(['sent_at' => $sentAt, 'response_due_at' => $dueAt]);
                $request->events()->create([
                    'request_item_id' => $item->id,
                    'actor_user_id' => $user->id,
                    'action' => 'offer_sent',
                    'data' => ['offer_id' => $offer->id, 'sent_at' => $sentAt, 'response_due_at' => $dueAt],
                    'created_at' => $sentAt,
                ]);
            }

            $request->events()->create([
                'actor_user_id' => $user->id,
                'action' => 'started',
                'data' => ['previous_admin_id' => $previousAdminId, 'assigned_admin_id' => $actor->id],
                'created_at' => $sentAt,
            ]);

            $result = $this->load($request->id);
            $result->setAttribute('available_actions', $this->availableActions($result, $user));

            return $result;
        });
    }

    public function summary(): array
    {
        return HiringRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function changeAssignee(int $requestId, int $adminId, User $actor, bool $reassign): HiringRequest
    {
        return DB::transaction(function () use ($requestId, $adminId, $actor, $reassign): HiringRequest {
            $request = HiringRequest::query()->lockForUpdate()->findOrFail($requestId);
            if (! in_array($request->status, [HiringRequestStatusEnum::PENDING, HiringRequestStatusEnum::ACTIVE], true)) {
                throw ValidationException::withMessages(['hiring_request' => [__('hiring.pending_only')]]);
            }
            if ($reassign && $request->assigned_admin_id === null) {
                throw ValidationException::withMessages(['admin_id' => [__('hiring.assignee_not_eligible')]]);
            }
            if (! $reassign && $request->assigned_admin_id !== null) {
                throw ValidationException::withMessages(['admin_id' => [__('hiring.assignee_not_eligible')]]);
            }

            $admin = Admin::query()->with('user')->findOrFail($adminId);
            if (! $admin->user?->is_active || ! $admin->user->can(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->value)) {
                throw ValidationException::withMessages(['admin_id' => [__('hiring.assignee_not_eligible')]]);
            }

            $previous = $request->assigned_admin_id;
            $request->update(['assigned_admin_id' => $admin->id]);
            $request->events()->create([
                'actor_user_id' => $actor->id,
                'action' => $reassign ? 'reassigned' : 'assigned',
                'data' => ['previous_admin_id' => $previous, 'assigned_admin_id' => $admin->id],
                'created_at' => now(),
            ]);

            $result = $this->load($request->id);
            $result->setAttribute('available_actions', $this->availableActions($result, $actor));

            return $result;
        });
    }

    private function availableActions(HiringRequest $request, User $user): array
    {
        $canAssign = $user->can(AdminPermissionEnum::HIRING_REQUESTS_ASSIGN->value);
        $canHandle = $user->can(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->value);
        $actorId = $user->admin?->id;
        $actions = [];

        if ($canAssign && $request->assigned_admin_id === null && $request->status === HiringRequestStatusEnum::PENDING) {
            $actions[] = 'assign';
        }
        if ($canAssign && $request->assigned_admin_id !== null && in_array($request->status, [HiringRequestStatusEnum::PENDING, HiringRequestStatusEnum::ACTIVE], true)) {
            $actions[] = 'reassign';
        }
        if ($canHandle && $request->status === HiringRequestStatusEnum::PENDING && ($request->assigned_admin_id === null || (int) $request->assigned_admin_id === (int) $actorId)) {
            $actions[] = 'start';
        }

        return $actions;
    }

    private function load(int $requestId): HiringRequest
    {
        return HiringRequest::query()->with(self::RELATIONS)->findOrFail($requestId);
    }
}
