<?php

namespace App\Modules\V1\Notifications\Application\Jobs;

use App\Modules\V1\Notifications\Application\Handlers\ClientManagementNotificationHandler;
use App\Modules\V1\Notifications\Application\Handlers\HelpRequestNotificationHandler;
use App\Modules\V1\Notifications\Application\Handlers\HiringNotificationHandler;
use App\Modules\V1\Notifications\Application\Handlers\RecordActivityNotificationHandler;
use App\Modules\V1\Notifications\Application\Handlers\TalentApplicationNotificationHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Resolves the recipients of a domain event off the request and after the transaction commits,
 * so a rolled-back action never notifies anyone.
 */
class DispatchDomainNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $source, public readonly array $context)
    {
        $this->afterCommit();
        $this->onQueue(config('notifications.queue'));
    }

    public function handle(Container $container): void
    {
        match ($this->source) {
            'hiring_event' => $container->make(HiringNotificationHandler::class)->handle($this->context['id']),
            'talent_application_event' => $container->make(TalentApplicationNotificationHandler::class)->handleEvent($this->context['id']),
            'talent_interview_action' => $container->make(TalentApplicationNotificationHandler::class)
                ->handleTalentAction($this->context['application_id'], $this->context['action']),
            'help_request_event' => $container->make(HelpRequestNotificationHandler::class)->handle($this->context['id']),
            'client_management_event' => $container->make(ClientManagementNotificationHandler::class)->handle($this->context['id']),
            'trash_event' => $container->make(RecordActivityNotificationHandler::class)->handleTrashEvent($this->context['id']),
            'record_created' => $container->make(RecordActivityNotificationHandler::class)->handleCreated(
                $this->context['class'], $this->context['id'], $this->context['actor_user_id'], $this->context['workspace_id'],
            ),
            default => null,
        };
    }
}
