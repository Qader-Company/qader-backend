<?php

namespace App\Modules\V1\HelpRequests\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestClosureOutcomeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestListScopeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class AdminHelpRequestService
{
    private const LIST_RELATIONS = ['workspace', 'createdByMember.client.user', 'assignedAdmin.user'];

    private const DETAIL_RELATIONS = [
        'workspace', 'createdByMember.client.user', 'assignedAdmin.user', 'closedByAdmin.user',
        'resultingHiringRequest', 'notes.admin.user',
    ];

    public function actor(User $user): Admin
    {
        return $user->admin()->with('user')->first()
            ?? throw new LogicException('Authenticated admin profile was not found.');
    }

    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        $actor = $this->actor($user);
        $paginator = HelpRequest::query()
            ->with(self::LIST_RELATIONS)
            ->withCount('notes')
            ->when(($filters['scope'] ?? HelpRequestListScopeEnum::ALL->value) === HelpRequestListScopeEnum::MINE->value, fn (Builder $query) => $query->where('assigned_admin_id', $actor->id))
            ->when($filters['status'] ?? null, fn (Builder $query, array $statuses) => $query->whereIn('status', $statuses))
            ->when($filters['help_type'] ?? null, fn (Builder $query, array $types) => $query->whereIn('help_type', $types))
            ->when($filters['assigned_admin_id'] ?? null, fn (Builder $query, int $adminId) => $query->where('assigned_admin_id', $adminId))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('reference', 'like', $like)
                        ->orWhereHas('workspace', fn (Builder $workspace) => $workspace->where('name', 'like', $like))
                        ->orWhereHas('createdByMember.client.user', fn (Builder $user) => $user
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like));
                });
            })
            ->latest()
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        $paginator->getCollection()->each(fn (HelpRequest $request) => $request
            ->setAttribute('available_actions', $this->availableActions($request, $user)));

        return $paginator;
    }

    public function show(int $requestId, User $user): HelpRequest
    {
        return $this->loadWithActions($requestId, $user);
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public function assignees()
    {
        $locale = app()->getLocale();

        return User::query()
            ->where('is_active', true)
            ->whereHas('admin')
            ->permission(AdminPermissionEnum::HELP_REQUESTS_HANDLE->value)
            ->with(['admin', 'roles'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (User $user) use ($locale): array {
                $role = $user->roles->first();

                return [
                    'id' => $user->admin->id,
                    'full_name' => trim($user->first_name.' '.$user->last_name),
                    'email' => $user->email,
                    'avatar' => UserAvatarUrlResolver::resolve($user->avatar),
                    'role' => $role === null ? null : (($locale === 'ar' ? $role->name_ar : $role->name_en) ?: $role->name),
                ];
            })->values();
    }

    /**
     * Assign one or more unassigned help requests to an eligible admin (bulk assign from the list).
     *
     * @param  array<int, int>  $requestIds
     * @return Collection<int, HelpRequest>
     */
    public function assign(array $requestIds, int $adminId, User $user): Collection
    {
        return DB::transaction(function () use ($requestIds, $adminId, $user): Collection {
            $admin = $this->eligibleAssignee($adminId);
            $requests = HelpRequest::query()->whereKey(array_values(array_unique($requestIds)))->orderBy('id')->lockForUpdate()->get();

            foreach ($requests as $request) {
                $this->ensureOpen($request);
                if ($request->assigned_admin_id !== null) {
                    throw ValidationException::withMessages(['help_request_ids' => [__('help_requests.already_assigned')]]);
                }
            }

            foreach ($requests as $request) {
                $request->update(['assigned_admin_id' => $admin->id]);
                $this->event($request, $user, 'assigned', ['previous_admin_id' => null, 'assigned_admin_id' => $admin->id]);
            }

            return HelpRequest::query()->with(self::LIST_RELATIONS)->withCount('notes')->whereKey($requests->modelKeys())->orderBy('id')->get()
                ->each(fn (HelpRequest $request) => $request->setAttribute('available_actions', $this->availableActions($request, $user)));
        });
    }

    public function reassign(int $requestId, int $adminId, User $user): HelpRequest
    {
        return DB::transaction(function () use ($requestId, $adminId, $user): HelpRequest {
            $request = HelpRequest::query()->lockForUpdate()->findOrFail($requestId);
            $this->ensureOpen($request);
            if ($request->assigned_admin_id === null) {
                throw ValidationException::withMessages(['admin_id' => [__('help_requests.not_assigned')]]);
            }

            $admin = $this->eligibleAssignee($adminId);
            $previous = $request->assigned_admin_id;
            $request->update(['assigned_admin_id' => $admin->id]);
            $this->event($request, $user, 'reassigned', ['previous_admin_id' => $previous, 'assigned_admin_id' => $admin->id]);

            return $this->loadWithActions($request->id, $user);
        });
    }

    public function start(int $requestId, User $user): HelpRequest
    {
        $actor = $this->actor($user);

        return DB::transaction(function () use ($requestId, $user, $actor): HelpRequest {
            $request = HelpRequest::query()->lockForUpdate()->findOrFail($requestId);
            if ($request->status !== HelpRequestStatusEnum::NEW) {
                throw ValidationException::withMessages(['help_request' => [__('help_requests.start_new_only')]]);
            }
            if ($request->assigned_admin_id !== null && (int) $request->assigned_admin_id !== (int) $actor->id) {
                throw ValidationException::withMessages(['help_request' => [__('help_requests.responsible_admin_only')]]);
            }

            $previous = $request->assigned_admin_id;
            $request->update([
                'assigned_admin_id' => $actor->id,
                'status' => HelpRequestStatusEnum::IN_PROGRESS,
                'started_at' => now(),
            ]);
            $this->event($request, $user, 'handling_started', ['previous_admin_id' => $previous, 'assigned_admin_id' => $actor->id]);

            return $this->loadWithActions($request->id, $user);
        });
    }

    public function addNote(int $requestId, string $body, User $user): HelpRequest
    {
        $actor = $this->actor($user);

        return DB::transaction(function () use ($requestId, $body, $user, $actor): HelpRequest {
            $request = HelpRequest::query()->lockForUpdate()->findOrFail($requestId);
            $note = $request->notes()->create([
                'admin_id' => $actor->id,
                'body' => trim($body),
                'created_at' => now(),
            ]);
            $this->event($request, $user, 'note_added', ['note_id' => $note->id]);

            return $this->loadWithActions($request->id, $user);
        });
    }

    /** @return Collection<int, HiringRequest> */
    public function closableHiringRequests(int $requestId): Collection
    {
        $request = HelpRequest::query()->findOrFail($requestId);

        return HiringRequest::query()
            ->where('workspace_id', $request->workspace_id)
            ->latest()
            ->latest('id')
            ->get(['id', 'reference', 'status', 'created_at']);
    }

    public function close(int $requestId, array $data, User $user): HelpRequest
    {
        $actor = $this->actor($user);

        return DB::transaction(function () use ($requestId, $data, $user, $actor): HelpRequest {
            $request = HelpRequest::query()->lockForUpdate()->findOrFail($requestId);
            if ($request->status !== HelpRequestStatusEnum::IN_PROGRESS) {
                throw ValidationException::withMessages(['help_request' => [__('help_requests.close_in_progress_only')]]);
            }
            if ((int) $request->assigned_admin_id !== (int) $actor->id) {
                throw ValidationException::withMessages(['help_request' => [__('help_requests.responsible_admin_only')]]);
            }

            $outcome = HelpRequestClosureOutcomeEnum::from($data['outcome']);
            $hiringRequestId = null;
            if ($outcome === HelpRequestClosureOutcomeEnum::NEW_HIRING_REQUEST_CREATED) {
                $hiringRequestId = (int) $data['hiring_request_id'];
                $belongsToWorkspace = HiringRequest::query()
                    ->whereKey($hiringRequestId)
                    ->where('workspace_id', $request->workspace_id)
                    ->exists();
                if (! $belongsToWorkspace) {
                    throw ValidationException::withMessages(['hiring_request_id' => [__('help_requests.hiring_request_other_workspace')]]);
                }
            }

            $request->update([
                'status' => HelpRequestStatusEnum::CLOSED,
                'closed_at' => now(),
                'closed_by_admin_id' => $actor->id,
                'closure_outcome' => $outcome,
                'closure_details' => filled($data['details'] ?? null) ? trim($data['details']) : null,
                'resulting_hiring_request_id' => $hiringRequestId,
            ]);
            $this->event($request, $user, 'closed', ['outcome' => $outcome->value, 'hiring_request_id' => $hiringRequestId]);

            return $this->loadWithActions($request->id, $user);
        });
    }

    /** @return array<int, string> */
    public function availableActions(HelpRequest $request, User $user): array
    {
        $canAssign = $user->can(AdminPermissionEnum::HELP_REQUESTS_ASSIGN->value);
        $canHandle = $user->can(AdminPermissionEnum::HELP_REQUESTS_HANDLE->value);
        $actorId = $user->admin?->id;
        $isOpen = $request->status !== HelpRequestStatusEnum::CLOSED;
        $isMine = $request->assigned_admin_id !== null && (int) $request->assigned_admin_id === (int) $actorId;
        $actions = [];

        if ($canAssign && $isOpen && $request->assigned_admin_id === null) {
            $actions[] = 'assign';
        }
        if ($canAssign && $isOpen && $request->assigned_admin_id !== null) {
            $actions[] = 'reassign';
        }
        if ($canHandle && $request->status === HelpRequestStatusEnum::NEW && ($request->assigned_admin_id === null || $isMine)) {
            $actions[] = 'start';
        }
        if ($canHandle && $request->status === HelpRequestStatusEnum::IN_PROGRESS && $isMine) {
            $actions[] = 'close';
        }
        if ($canHandle) {
            $actions[] = 'add_note';
        }

        return $actions;
    }

    private function eligibleAssignee(int $adminId): Admin
    {
        $admin = Admin::query()->with('user')->findOrFail($adminId);
        if (! $admin->user?->is_active || ! $admin->user->can(AdminPermissionEnum::HELP_REQUESTS_HANDLE->value)) {
            throw ValidationException::withMessages(['admin_id' => [__('help_requests.assignee_not_eligible')]]);
        }

        return $admin;
    }

    private function ensureOpen(HelpRequest $request): void
    {
        if ($request->status === HelpRequestStatusEnum::CLOSED) {
            throw ValidationException::withMessages(['help_request' => [__('help_requests.closed_request')]]);
        }
    }

    private function event(HelpRequest $request, User $user, string $action, array $data = []): void
    {
        $request->events()->create([
            'actor_user_id' => $user->id,
            'action' => $action,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    private function loadWithActions(int $requestId, User $user): HelpRequest
    {
        $request = HelpRequest::query()->with(self::DETAIL_RELATIONS)->withCount('notes')->findOrFail($requestId);
        $request->setAttribute('available_actions', $this->availableActions($request, $user));

        return $request;
    }
}
