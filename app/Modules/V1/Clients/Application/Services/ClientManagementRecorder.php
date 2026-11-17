<?php

namespace App\Modules\V1\Clients\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\ClientManagementEvent;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientManagementActionEnum;
use App\Modules\V1\Users\Domain\Models\User;
use LogicException;

class ClientManagementRecorder
{
    public function actor(User $user): Admin
    {
        return $user->admin()->first()
            ?? throw new LogicException('Authenticated admin profile was not found.');
    }

    public function record(
        Admin $actor,
        ClientManagementActionEnum $action,
        int $clientId,
        ?int $workspaceId = null,
        ?int $memberId = null,
        ?string $reason = null,
    ): void {
        ClientManagementEvent::query()->create([
            'client_id' => $clientId,
            'workspace_id' => $workspaceId,
            'workspace_member_id' => $memberId,
            'actor_admin_id' => $actor->id,
            'action' => $action,
            'reason' => filled($reason) ? trim($reason) : null,
            'created_at' => now(),
        ]);
    }
}
