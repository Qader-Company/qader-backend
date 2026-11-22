<?php

namespace App\Modules\V1\Notifications\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Support\Facades\DB;

class NotificationRecipients
{
    public function __construct(private readonly WorkspaceAccessService $access) {}

    /** Active workspace members (owner included) who hold the given workspace permission. */
    public function workspaceMembersWith(int $workspaceId, string $permission = ClientPermissionEnum::HIRING_REQUESTS_MANAGE->value): array
    {
        return WorkspaceMember::query()
            ->where('workspace_id', $workspaceId)
            ->where('status', WorkspaceMemberStatusEnum::ACTIVE->value)
            ->with(['roles.permissions', 'client'])
            ->get()
            ->filter(fn (WorkspaceMember $member): bool => $this->access->allows($member, $permission))
            ->map(fn (WorkspaceMember $member): ?int => $member->client?->user_id)
            ->filter()->values()->all();
    }

    public function workspaceOwner(int $workspaceId): ?int
    {
        return Workspace::withTrashed()->with('owner')->find($workspaceId)?->owner?->user_id;
    }

    public function admin(?int $adminId): ?int
    {
        return $adminId === null ? null : Admin::query()->whereKey($adminId)->value('user_id');
    }

    /** @return array<int, int> */
    public function superAdmins(): array
    {
        $tables = config('permission.table_names');

        return DB::table($tables['model_has_roles'])
            ->join($tables['roles'], $tables['roles'].'.id', '=', $tables['model_has_roles'].'.role_id')
            ->where($tables['roles'].'.name', AdminRoleEnum::SUPER_ADMIN->value)
            ->pluck($tables['model_has_roles'].'.'.config('permission.column_names.model_morph_key', 'model_id'))
            ->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * Recipients and target for one side of a hiring request item.
     * The item must have `talent` and `hiringRequest` loaded.
     *
     * @return array{ids: array<int, int|null>, target_id: ?int, workspace_id: ?int}
     */
    public function hiringParty(string $party, HiringRequest $request, ?HiringRequestItem $item): array
    {
        return match ($party) {
            'talent' => [
                'ids' => [$item?->talent?->user_id],
                'target_id' => $item?->id,
                'workspace_id' => null,
            ],
            'client' => [
                'ids' => $this->workspaceMembersWith($request->workspace_id),
                'target_id' => $request->id,
                'workspace_id' => $request->workspace_id,
            ],
            'admin' => [
                'ids' => [$this->admin($request->assigned_admin_id)],
                'target_id' => $request->id,
                'workspace_id' => null,
            ],
        };
    }
}
