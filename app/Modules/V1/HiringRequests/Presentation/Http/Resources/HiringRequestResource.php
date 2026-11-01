<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminSummaryResource;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HiringRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->items : collect();
        $isAdmin = str_contains((string) $request->route()?->uri(), '/admin/');

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status,
            'workspace' => $this->relationLoaded('workspace') ? [
                'id' => $this->workspace->id,
                'name' => $this->workspace->name,
            ] : null,
            'assigned_admin' => $this->assignedAdmin === null ? null : new AdminSummaryResource($this->assignedAdmin),
            'items_count' => isset($this->items_count) ? (int) $this->items_count : $items->count(),
            'status_counts' => $items->isEmpty()
                ? (object) []
                : $items->groupBy(fn ($item) => $item->status instanceof BackedEnum ? $item->status->value : $item->status)
                    ->map->count(),
            'totals_by_currency' => $items->isEmpty() ? [] : $items
                ->filter(fn ($item) => $item->latestOffer?->currency !== null)
                ->groupBy(fn ($item) => $item->latestOffer->currency->code)
                ->map(fn ($currencyItems, string $code): array => [
                    'currency' => $code,
                    'amount' => (string) $currencyItems->sum(fn ($item) => (float) $item->latestOffer->salary_amount),
                ])->values(),
            'items' => HiringRequestItemResource::collection($this->whenLoaded('items')),
            'available_actions' => $this->available_actions ?? ['view'],
            'activity' => $isAdmin && $this->relationLoaded('events')
                ? $this->events->map(fn ($event): array => [
                    'id' => $event->id,
                    'action' => $event->action,
                    'data' => $event->data,
                    'actor' => $event->actor === null ? null : [
                        'id' => $event->actor->id,
                        'full_name' => trim($event->actor->first_name.' '.$event->actor->last_name),
                    ],
                    'created_at' => $event->created_at,
                ])->values()
                : null,
            'submitted_at' => $this->submitted_at,
            'started_at' => $this->started_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
