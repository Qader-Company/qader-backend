<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Presentation\Http\Resources\NotificationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;

/** Shared notification list/read logic; the caller passes the user's notifications, optionally scoped. */
class NotificationInbox
{
    public function list(Request $request, MorphMany|Builder $notifications): array
    {
        $validated = validator($request->query(), [
            'unread_only' => ['nullable', Rule::in(['true', 'false', '1', '0', 1, 0, true, false])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();

        $page = $notifications
            ->when($request->boolean('unread_only'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 20));

        return [
            'notifications' => NotificationResource::collection($page->getCollection()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    public function unreadCount(MorphMany|Builder $notifications): int
    {
        return $notifications->whereNull('read_at')->count();
    }

    public function markAsRead(MorphMany|Builder $notifications, string $id): DatabaseNotification
    {
        /** @var DatabaseNotification $record */
        $record = $notifications->whereKey($id)->firstOrFail();
        $record->markAsRead();

        return $record->refresh();
    }

    public function markAllAsRead(MorphMany|Builder $notifications): void
    {
        $notifications->whereNull('read_at')->update(['read_at' => now()]);
    }
}
