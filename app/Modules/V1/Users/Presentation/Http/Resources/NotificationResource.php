<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use App\Modules\V1\Notifications\Application\Support\NotificationMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];
        $message = NotificationMessage::render($data);

        return [
            'id' => $this->id,
            'type' => $this->type,
            // Rendered in the request language from the stored translation keys.
            'title' => $message['title'],
            'body' => $message['body'],
            'target_id' => $data['target_id'] ?? null,
            // Only client notifications carry a workspace (/client/{workspace}/notifications).
            'workspace_id' => $this->when(isset($data['workspace_id']), fn () => $data['workspace_id']),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
