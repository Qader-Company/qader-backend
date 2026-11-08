<?php

namespace App\Modules\V1\Workspaces\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\Clients\Application\UseCases\CreateClientAccountUseCase;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Application\Mail\WorkspaceMemberAccountMail;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceTeamService
{
    public function __construct(private readonly WorkspaceAccessService $access, private readonly CreateClientAccountUseCase $createClient) {}

    public function members(User $user, Workspace $workspace)
    {
        $this->access->authorize($user, $workspace, 'client.members.view');

        return $workspace->members()->with(['client.user', 'roles'])->latest('joined_at')->get();
    }

    public function member(User $user, Workspace $workspace, WorkspaceMember $member): WorkspaceMember
    {
        $this->access->authorize($user, $workspace, 'client.members.view');
        $this->same($workspace, $member);

        return $member->load(['client.user', 'roles']);
    }

    public function createMember(User $user, Workspace $workspace, array $data): WorkspaceMember
    {
        $this->access->authorize($user, $workspace, 'client.members.create');
        $roles = $this->rolesForAssignment($workspace, $data['role_ids']);

        [$member, $temporaryPassword, $existingAccount] = DB::transaction(function () use ($workspace, $roles, $data): array {
            $account = User::query()
                ->where('email', $data['email'])
                ->where('type', PortalTypeEnum::CLIENT)
                ->first();
            $temporaryPassword = null;
            $existingAccount = $account !== null;

            if (! $account) {
                $temporaryPassword = Str::password(12);
                $account = $this->createClient->execute([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'password' => $temporaryPassword,
                    'email_verified_at' => now(),
                ]);
                $account->forceFill(['must_change_password' => true])->save();
            } else {
                $account = $this->createClient->ensureProfile($account);
            }

            $member = WorkspaceMember::withTrashed()
                ->where('workspace_id', $workspace->id)
                ->where('client_id', $account->client->id)
                ->first();

            if ($member && ! $member->trashed()) {
                throw ValidationException::withMessages(['email' => [__('workspaces.already_member')]]);
            }

            if ($member) {
                $member->restore();
                $member->update(['status' => WorkspaceMemberStatusEnum::ACTIVE, 'joined_at' => now()]);
            } else {
                $member = WorkspaceMember::query()->create([
                    'workspace_id' => $workspace->id,
                    'client_id' => $account->client->id,
                    'status' => WorkspaceMemberStatusEnum::ACTIVE,
                    'joined_at' => now(),
                ]);
            }

            $member->roles()->sync($roles->pluck('id'));

            return [$member->load(['workspace', 'client.user', 'roles']), $temporaryPassword, $existingAccount];
        });

        Mail::to($member->client->user->email)->queue(new WorkspaceMemberAccountMail(
            name: trim($member->client->user->first_name.' '.$member->client->user->last_name),
            email: $member->client->user->email,
            workspaceName: $workspace->name,
            temporaryPassword: $temporaryPassword,
            existingAccount: $existingAccount,
        ));

        return $member;
    }

    public function updateMember(User $user, Workspace $workspace, WorkspaceMember $member, array $data): WorkspaceMember
    {
        $actor = $this->access->authorize($user, $workspace, 'client.members.manage');
        $this->editableMember($workspace, $member);
        $roles = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        return DB::transaction(function () use ($member, $data, $roles, $workspace, $actor) {
            $member->update($data);
            if ($roles !== null) {
                $valid = $this->rolesForAssignment($workspace, $roles);
                if ($actor->is($member) && ! $valid->contains(fn ($r) => $r->permissions()->where('name', 'client.members.manage')->exists())) {
                    throw new DomainException(__('workspaces.cannot_remove_own_management'));
                }

                $member->roles()->sync($valid->pluck('id'));
            }

            return $member->refresh()->load(['client.user', 'roles']);
        });
    }

    public function deleteMember(User $user, Workspace $workspace, WorkspaceMember $member): void
    {
        $this->access->authorize($user, $workspace, 'client.members.manage');
        $this->editableMember($workspace, $member);
        $member->delete();
    }

    private function rolesForAssignment(Workspace $workspace, array $ids)
    {
        $roles = $workspace->roles()->whereIn('id', $ids)->where('is_active', true)->where('key', '!=', 'owner')->with('permissions')->get();
        if ($roles->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['role_ids' => [__('workspaces.invalid_roles')]]);
        }

        return $roles;
    }

    private function editableMember(Workspace $w, WorkspaceMember $m): void
    {
        $this->same($w, $m);
        if ($m->roles()->where('key', 'owner')->exists()) {
            throw new DomainException(__('workspaces.owner_member_protected'));
        }
    }

    private function same(Workspace $w, object $model): void
    {
        if ((int) $model->workspace_id !== $w->id) {
            abort(404);
        }
    }
}
