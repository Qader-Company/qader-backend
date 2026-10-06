<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionGroupResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $section = $this->resource['section'];

        return [
            'key' => $section,
            'label' => __("permissions.sections.{$section}"),
            'permissions' => PermissionResource::collection(
                $this->resource['permissions']
            ),
        ];
    }
}
