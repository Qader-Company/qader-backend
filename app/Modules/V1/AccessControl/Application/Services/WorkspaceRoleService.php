<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceRoleService
{
    public function __construct(private readonly WorkspaceAccessService $access) {}

    public function permissions(User $user, Workspace $workspace): array
    {
        $this->access->authorize($user, $workspace, 'client.roles.view');

        return collect(ClientPermissionEnum::cases())
            ->groupBy(fn ($permission) => $permission->section())
            ->map(fn ($permissions, $section) => [
                'section' => $section,
                'label' => __('permissions.sections.'.$section),
                'permissions' => $permissions->map(fn ($permission) => [
                    'value' => $permission->value,
                    'label' => $permission->label(),
                ])->values(),
            ])->values()->all();
    }

    public function roles(User $user, Workspace $workspace)
    {
        $this->access->authorize($user, $workspace, 'client.roles.view');

        return $workspace->roles()->with('permissions')->withCount('members')->orderBy('name_en')->get();
    }

    public function role(User $user, Workspace $workspace, WorkspaceRole $role): WorkspaceRole
    {
        $this->access->authorize($user, $workspace, 'client.roles.view');
        $this->ensureSameWorkspace($workspace, $role);

        return $role->load('permissions')->loadCount('members');
    }

    public function createRole(User $user, Workspace $workspace, array $data): WorkspaceRole
    {
        $this->access->authorize($user, $workspace, 'client.roles.manage');
        $permissions = Arr::pull($data, 'permissions', []);
        $key = Str::slug($data['name_en'], '_');

        if ($key === 'owner') {
            throw new DomainException(__('workspaces.owner_role_reserved'));
        }

        if ($workspace->roles()->where('key', $key)->exists()) {
            throw ValidationException::withMessages(['name_en' => [__('workspaces.role_name_exists')]]);
        }

        return DB::transaction(function () use ($workspace, $data, $key, $permissions) {
            $role = $workspace->roles()->create(['key' => $key, ...$data]);
            $role->permissions()->sync($this->permissionIds($permissions));

            return $role->load('permissions')->loadCount('members');
        });
    }

    public function updateRole(User $user, Workspace $workspace, WorkspaceRole $role, array $data): WorkspaceRole
    {
        $this->access->authorize($user, $workspace, 'client.roles.manage');
        $this->ensureEditable($workspace, $role);
        $permissions = Arr::pull($data, 'permissions', null);

        return DB::transaction(function () use ($workspace, $role, $data, $permissions) {
            if (isset($data['name_en'])) {
                $data['key'] = Str::slug($data['name_en'], '_');
                if ($workspace->roles()->where('key', $data['key'])->where('id', '!=', $role->id)->exists()) {
                    throw ValidationException::withMessages(['name_en' => [__('workspaces.role_name_exists')]]);
                }
            }

            $role->update($data);
            if ($permissions !== null) {
                $role->permissions()->sync($this->permissionIds($permissions));
            }

            return $role->refresh()->load('permissions')->loadCount('members');
        });
    }

    public function deleteRole(User $user, Workspace $workspace, WorkspaceRole $role): void
    {
        $this->access->authorize($user, $workspace, 'client.roles.manage');
        $this->ensureEditable($workspace, $role);

        if ($role->members()->exists()) {
            throw new DomainException(__('workspaces.role_in_use'));
        }

        $role->delete();
    }

    private function permissionIds(array $names): array
    {
        $ids = Permission::query()->where('portal', 'client')->whereIn('name', $names)->pluck('id')->all();
        if (count($ids) !== count(array_unique($names))) {
            throw ValidationException::withMessages(['permissions' => [__('workspaces.invalid_permissions')]]);
        }

        return $ids;
    }

    private function ensureEditable(Workspace $workspace, WorkspaceRole $role): void
    {
        $this->ensureSameWorkspace($workspace, $role);
        if ($role->key === 'owner') {
            throw new DomainException(__('workspaces.owner_role_protected'));
        }
    }

    private function ensureSameWorkspace(Workspace $workspace, WorkspaceRole $role): void
    {
        if ((int) $role->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }
    }
}
