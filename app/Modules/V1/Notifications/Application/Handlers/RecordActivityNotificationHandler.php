<?php

namespace App\Modules\V1\Notifications\Application\Handlers;

use App\Modules\V1\Notifications\Application\Services\NotificationRecipients;
use App\Modules\V1\Notifications\Application\Services\Notifier;
use App\Modules\V1\Trash\Application\Services\TrashRegistry;
use App\Modules\V1\Trash\Domain\Models\TrashEvent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Create / delete / restore / permanent-remove notifications for every record in the Trash registry:
 * actions by admins go to the super admins, actions by workspace members go to the workspace owner.
 */
class RecordActivityNotificationHandler
{
    private const TYPES = [
        'created' => 'record_created',
        'deleted' => 'record_deleted',
        'restored' => 'record_restored',
        'removed' => 'record_removed',
    ];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly NotificationRecipients $recipients,
        private readonly TrashRegistry $registry,
    ) {}

    public function handleTrashEvent(int $eventId): void
    {
        $event = TrashEvent::query()->find($eventId);
        $definition = $event === null ? null : $this->definitionForType($event->resource_type);
        if ($definition === null) {
            return;
        }

        $this->notify($event->action, $definition[0], $definition[1], (int) $event->resource_id, $event->actor_user_id, $event->workspace_id);
    }

    /** @param  class-string<Model>  $class */
    public function handleCreated(string $class, int $id, int $actorUserId, ?int $workspaceId): void
    {
        $key = collect($this->registry->definitions())->search(fn (array $definition): bool => $definition['model'] === $class);
        if ($key === false) {
            return;
        }

        $this->notify('created', $key, $this->registry->definition($key), $id, $actorUserId, $workspaceId);
    }

    private function notify(string $action, string $key, array $definition, int $id, ?int $actorUserId, ?int $workspaceId): void
    {
        $actor = $actorUserId === null ? null : User::query()->find($actorUserId);
        if ($actor === null || ! isset(self::TYPES[$action])) {
            return;
        }

        $portal = $actor->type instanceof BackedEnum ? $actor->type->value : $actor->type;
        $model = $this->model($definition, $id);
        if ($key === 'workspaces') {
            $workspaceId = $id;
        }

        $params = [
            'actor' => trim($actor->first_name.' '.$actor->last_name),
            'name' => $model === null ? '#'.$id : $this->recordName($model),
            // Trash Registry key (e.g. `skills`) so the apps know which page `target_id` belongs to.
            'resource' => $key,
        ];
        $translated = ['record' => "notifications.records.{$key}"];

        if ($portal === PortalTypeEnum::ADMIN->value) {
            $this->notifier->send(
                $this->recipients->superAdmins(), self::TYPES[$action], $params, $translated,
                $id, null, $actor->id,
            );

            return;
        }

        if ($portal === PortalTypeEnum::CLIENT->value && $workspaceId !== null) {
            $this->notifier->send(
                [$this->recipients->workspaceOwner($workspaceId)], self::TYPES[$action], $params, $translated,
                $id, $workspaceId, $actor->id,
            );
        }
    }

    /** @return array{0: string, 1: array}|null */
    private function definitionForType(?string $resourceType): ?array
    {
        foreach ($this->registry->definitions() as $key => $definition) {
            if (class_basename($definition['model']) === $resourceType) {
                return [$key, $definition];
            }
        }

        return null;
    }

    private function model(array $definition, int $id): ?Model
    {
        $class = $definition['model'];
        $query = in_array(SoftDeletes::class, class_uses_recursive($class), true) ? $class::withTrashed() : $class::query();

        return $query->with($definition['relations'])->find($id);
    }

    private function recordName(Model $model): string
    {
        $person = match (true) {
            $model->relationLoaded('user') => $model->getRelation('user'),
            $model->relationLoaded('client') => $model->getRelation('client')?->relationLoaded('user') ? $model->getRelation('client')->getRelation('user') : null,
            default => null,
        };
        if ($person !== null) {
            return trim($person->first_name.' '.$person->last_name);
        }
        if ($model instanceof Workspace) {
            return (string) $model->name;
        }

        foreach (['name', 'name_en', 'title', 'reference', 'applicant_name', 'key', 'code'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }
}
