<?php

namespace App\Modules\V1\Talents\Presentation\Http\Resources;

use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentProfileResource extends JsonResource
{
    public function __construct(Talent $resource, private readonly array $progress)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sections' => collect($this->progress['steps'])
                ->map(fn (array $step): array => [
                    'key' => $step['key'],
                    'visible_fields' => $step['visible_fields'],
                    'required_fields' => $step['required_fields'],
                    'data' => $step['data'],
                ])
                ->values()
                ->all(),
        ];
    }
}
