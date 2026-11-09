<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpRequestNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->relationLoaded('admin') && $this->admin !== null ? new AdminSummaryResource($this->admin) : null,
            'created_at' => $this->created_at,
        ];
    }
}
