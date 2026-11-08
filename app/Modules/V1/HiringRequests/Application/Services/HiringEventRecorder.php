<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\Users\Domain\Models\User;

class HiringEventRecorder
{
    public function record(
        HiringRequestItem $item,
        ?User $actor,
        string $action,
        array $data = [],
    ): void {
        $item->hiringRequest->events()->create([
            'request_item_id' => $item->id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'data' => $data === [] ? null : $data,
            'created_at' => now(),
        ]);
    }
}
