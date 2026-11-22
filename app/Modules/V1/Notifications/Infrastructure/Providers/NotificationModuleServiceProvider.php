<?php

namespace App\Modules\V1\Notifications\Infrastructure\Providers;

use App\Modules\V1\Clients\Domain\Models\ClientManagementEvent;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequestEvent;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestEvent;
use App\Modules\V1\Notifications\Application\Jobs\DispatchDomainNotification;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;
use App\Modules\V1\Trash\Application\Services\TrashRegistry;
use App\Modules\V1\Trash\Domain\Models\TrashEvent;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

/**
 * Every workflow already writes an audit row (`*_events`, `trash_events`); notifications hook on those rows
 * so no workflow has to remember to notify. Recipients are resolved in a queued job after commit.
 */
class NotificationModuleServiceProvider extends ServiceProvider
{
    private const EVENT_SOURCES = [
        HiringRequestEvent::class => 'hiring_event',
        TalentApplicationEvent::class => 'talent_application_event',
        HelpRequestEvent::class => 'help_request_event',
        ClientManagementEvent::class => 'client_management_event',
        TrashEvent::class => 'trash_event',
    ];

    public function boot(): void
    {
        foreach (self::EVENT_SOURCES as $class => $source) {
            $class::created(fn (Model $event) => DispatchDomainNotification::dispatch($source, ['id' => $event->getKey()]));
        }

        // "Record added" notifications for every record type managed by the Trash registry.
        foreach ($this->app->make(TrashRegistry::class)->definitions() as $definition) {
            $definition['model']::created(function (Model $model): void {
                $actor = auth()->user();
                $portal = $actor?->type instanceof BackedEnum ? $actor->type->value : $actor?->type;
                if (! in_array($portal, [PortalTypeEnum::ADMIN->value, PortalTypeEnum::CLIENT->value], true)) {
                    return;
                }

                DispatchDomainNotification::dispatch('record_created', [
                    'class' => $model::class,
                    'id' => $model->getKey(),
                    'actor_user_id' => $actor->getKey(),
                    'workspace_id' => $model->getAttribute('workspace_id'),
                ]);
            });
        }
    }
}
