<?php

namespace App\Modules\V1\Notifications\Application\Services;

use App\Modules\V1\Notifications\Application\Notifications\PlatformNotification;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    /**
     * @param  iterable<int|null>  $userIds
     * @param  array<string, scalar|null>  $params  values used as-is in the text
     * @param  array<string, string>  $translatedParams  translation keys rendered in the reader's language
     */
    public function send(
        iterable $userIds,
        string $type,
        array $params = [],
        array $translatedParams = [],
        ?int $targetId = null,
        ?int $workspaceId = null,
        ?int $exceptUserId = null,
    ): void {
        $ids = collect($userIds)->filter()->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $exceptUserId)
            ->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        // Always notify the portal User model so `notifiable_type` matches what the notification APIs read.
        $users = User::query()->whereIn('id', $ids)->where('is_active', true)->get();
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new PlatformNotification([
            'type' => $type,
            'title' => "notifications.{$type}.title",
            'body' => "notifications.{$type}.body",
            'params' => array_map(fn ($value) => $value ?? '', $params),
            // Only present when a param itself must be translated (e.g. a record type or a decision label).
            ...($translatedParams === [] ? [] : ['translated_params' => $translatedParams]),
            'target_id' => $targetId,
            // Only client notifications are workspace-scoped (/client/{workspace}/notifications).
            ...($workspaceId === null ? [] : ['workspace_id' => $workspaceId]),
        ]));
    }
}
