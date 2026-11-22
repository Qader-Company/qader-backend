<?php

namespace App\Modules\V1\Notifications\Application\Handlers;

use App\Modules\V1\Clients\Application\Mail\ClientAccountStatusMail;
use App\Modules\V1\Clients\Domain\Models\ClientManagementEvent;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientManagementActionEnum;
use App\Modules\V1\Notifications\Application\Services\NotificationRecipients;
use App\Modules\V1\Notifications\Application\Services\Notifier;
use App\Modules\V1\Notifications\Application\Support\NotificationMessage;
use Illuminate\Support\Facades\Mail;

/** Admin client-management actions: account suspension is emailed (tokens are revoked), workspace status is notified. */
class ClientManagementNotificationHandler
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
    ) {}

    public function handle(int $eventId): void
    {
        $event = ClientManagementEvent::query()->with(['client.user', 'workspace', 'actor'])->find($eventId);
        if ($event === null) {
            return;
        }

        match ($event->action) {
            ClientManagementActionEnum::CLIENT_SUSPENDED => $this->mail($event, suspended: true),
            ClientManagementActionEnum::CLIENT_REACTIVATED => $this->mail($event, suspended: false),
            ClientManagementActionEnum::WORKSPACE_DEACTIVATED => $this->workspace($event, 'workspace_deactivated'),
            ClientManagementActionEnum::WORKSPACE_ACTIVATED => $this->workspace($event, 'workspace_activated'),
            default => null,
        };
    }

    private function mail(ClientManagementEvent $event, bool $suspended): void
    {
        $user = $event->client?->user;
        if ($user === null || blank($user->email)) {
            return;
        }

        Mail::to($user->email)
            ->locale(NotificationMessage::localeFor($user))
            ->queue(new ClientAccountStatusMail(
                trim($user->first_name.' '.$user->last_name),
                $suspended,
                $suspended ? $event->reason : null,
            ));
    }

    private function workspace(ClientManagementEvent $event, string $type): void
    {
        if ($event->workspace_id === null) {
            return;
        }

        $this->notifier->send(
            [$this->recipients->workspaceOwner($event->workspace_id)],
            $type,
            ['workspace' => $event->workspace?->name],
            [],
            $event->workspace_id,
            $event->workspace_id,
            $event->actor?->user_id,
        );
    }
}
