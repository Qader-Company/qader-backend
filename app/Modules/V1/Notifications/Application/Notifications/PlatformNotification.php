<?php

namespace App\Modules\V1\Notifications\Application\Notifications;

use App\Modules\V1\Notifications\Application\Support\NotificationMessage;
use App\Modules\V1\Notifications\Infrastructure\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * One generic notification for every platform event. The payload carries the short
 * `type` (the apps map it to a page), translation keys, params and the target record.
 */
class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{type: string, title: string, body: string, params: array, translated_params?: array, target_id: ?int, workspace_id?: int}  $payload
     */
    public function __construct(public readonly array $payload)
    {
        $this->afterCommit();
        $this->onQueue(config('notifications.queue'));
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->payload['type'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        $message = NotificationMessage::render($this->payload, NotificationMessage::localeFor($notifiable));

        return [
            'title' => (string) $message['title'],
            'body' => (string) $message['body'],
            'data' => [
                'notification_id' => (string) $this->id,
                'type' => $this->payload['type'],
                'target_id' => (string) ($this->payload['target_id'] ?? ''),
                'workspace_id' => (string) ($this->payload['workspace_id'] ?? ''),
            ],
        ];
    }
}
