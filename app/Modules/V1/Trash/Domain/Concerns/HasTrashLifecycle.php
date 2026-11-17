<?php

namespace App\Modules\V1\Trash\Domain\Concerns;

use App\Modules\V1\Trash\Domain\Models\TrashEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

trait HasTrashLifecycle
{
    use SoftDeletes;

    public static function bootHasTrashLifecycle(): void
    {
        static::deleted(function ($model): void {
            if ($model->isForceDeleting() || ! Schema::hasTable('trash_events')) {
                return;
            }

            $model->recordTrashEvent('deleted');
        });

        static::restored(function ($model): void {
            if (! Schema::hasTable('trash_events')) {
                return;
            }

            $model->recordTrashEvent('restored');
        });
    }

    public function initializeHasTrashLifecycle(): void
    {
        $this->mergeCasts([
            'deleted_at' => 'datetime',
            'removed_from_trash_at' => 'datetime',
        ]);
    }

    public function scopeVisibleInTrash(Builder $query): Builder
    {
        return $query->onlyTrashed()->whereNull($this->qualifyColumn('removed_from_trash_at'));
    }

    public function isVisibleInTrash(): bool
    {
        return $this->trashed() && $this->removed_from_trash_at === null;
    }

    public function trashResourceType(): string
    {
        return class_basename($this);
    }

    private function recordTrashEvent(string $action): void
    {
        $actor = auth()->user();
        $accountUser = method_exists($this, 'user') ? $this->user : null;

        TrashEvent::query()->create([
            'resource_type' => $this->trashResourceType(),
            'resource_id' => $this->getKey(),
            'action' => $action,
            'actor_user_id' => $actor?->getKey(),
            'portal' => $actor?->type?->value ?? $actor?->type,
            'workspace_id' => $this->getAttribute('workspace_id'),
            'reason' => request()?->input('reason'),
            'metadata' => $accountUser ? [
                'account_was_active' => (bool) $accountUser->is_active,
            ] : null,
            'created_at' => now(),
        ]);
    }
}
