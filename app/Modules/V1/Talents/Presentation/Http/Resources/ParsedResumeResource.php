<?php

namespace App\Modules\V1\Talents\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParsedResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
