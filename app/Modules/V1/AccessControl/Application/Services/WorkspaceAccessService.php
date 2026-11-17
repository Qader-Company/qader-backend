<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WorkspaceAccessService
{
    public function member(User $user, Workspace $workspace): WorkspaceMember
    {
        $client = $user->loadMissing('client')->client;
        $member = $client ? WorkspaceMember::query()->where('workspace_id', $workspace->id)->where('client_id', $client->id)->with('roles.permissions')->first() : null;
        if (! $member || $member->status !== WorkspaceMemberStatusEnum::ACTIVE) {
            throw (new ModelNotFoundException)->setModel(
                Workspace::class,
                [$workspace->id],
            );
        }

        return $member;
    }

    public function authorize(User $user, Workspace $workspace, string $permission): WorkspaceMember
    {
        $member = $this->member($user, $workspace);
        $this->ensureActive($workspace);
        if (! $this->allows($member, $permission)) {
            throw new AuthorizationException(__('workspaces.permission_denied'));
        }

        return $member;
    }

    /** An inactive workspace pauses all its activity: members keep seeing it exists, but every action returns 403. */
    public function ensureActive(Workspace $workspace): void
    {
        if (! $workspace->isActive()) {
            throw new AuthorizationException(__('workspaces.inactive'));
        }
    }

    public function allows(WorkspaceMember $member, string $permission): bool
    {
        return $member->roles->contains(
            fn ($role): bool => $role->key === 'owner'
                || ($role->is_active && $role->permissions->contains('name', $permission))
        );
    }
}
