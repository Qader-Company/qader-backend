<?php

namespace App\Modules\V1\Skills\Presentation\Http\Resources;

use App\Modules\V1\ProfessionalFields\Presentation\Http\Resources\ProfessionalFieldResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $professionalFields = $this->professionalFields();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'professional_fields' => ProfessionalFieldResource::collection(
                $professionalFields,
            ),
            'is_active' => (bool) $this->is_active,
        ];
    }

    private function professionalFields(): mixed
    {
        if (isset($this->resource->professional_fields)) {
            return $this->resource->professional_fields;
        }

        if (
            $this->resource instanceof Model
            && $this->resource->relationLoaded('professionalFields')
        ) {
            return $this->resource->getRelation('professionalFields');
        }

        return collect();
    }
}
