<?php

namespace App\Modules\V1\Talents\Presentation\Http\Resources;

use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentOnboardingResource extends JsonResource
{
    public function __construct(
        Talent $resource,
        private readonly array $state,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'talent_id' => $this->id,
            'current_step' => $this->state['current_step'],
            'required_steps_completed' => $this->state['required_steps_completed'],
            'completion_percentage' => $this->state['completion_percentage'],
            'steps' => array_values($this->state['steps']),
        ];
    }
}
