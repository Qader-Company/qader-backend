<?php

namespace App\Modules\V1\Clients\Presentation\Http\Resources;

use App\Support\ApiMetadata\EnumLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminClientWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $industries = $this->relationLoaded('industries')
            ? $this->industries->map(fn ($industry): array => ['id' => $industry->id, 'name' => $industry->name])->values()
            : collect();
        $fields = $this->relationLoaded('professionalFields')
            ? $this->professionalFields->map(fn ($field): array => ['id' => $field->id, 'name' => $field->name])->values()
            : collect();
        $isDetail = $this->resource->offsetExists('kpis');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'industries' => $industries,
            'professional_fields' => $fields,
            // Figma shows a single "Field of Work"; the workspace supports many, so the first one is the display value.
            'field_of_work' => $fields->first()['name'] ?? $industries->first()['name'] ?? null,
            'status' => $this->status?->value,
            'status_label' => EnumLabel::for($this->status, 'workspace_statuses'),
            'created_at' => $this->created_at,
            ...($isDetail ? [
                'owner' => $this->owner === null ? null : [
                    'client_id' => $this->owner->id,
                    'full_name' => trim(($this->owner->user?->first_name ?? '').' '.($this->owner->user?->last_name ?? '')),
                ],
                'kpis' => $this->kpis,
                'information' => [
                    'description' => $this->description,
                    'is_company' => (bool) $this->is_company,
                    'cr_number' => $this->cr_number,
                    'tax_card' => $this->taxCard(),
                    'country' => $this->country === null ? null : ['id' => $this->country->id, 'name' => $this->country->name],
                    'city' => $this->city === null ? null : ['id' => $this->city->id, 'name' => $this->city->name],
                    'size_band' => $this->size_band?->value,
                    'website_url' => $this->website_url,
                    'social_links' => $this->socialLinks->map(fn ($link): array => [
                        'platform' => $link->platform->value,
                        'platform_label' => EnumLabel::for($link->platform, 'workspace_social_platforms'),
                        'url' => $link->url,
                    ])->values(),
                ],
            ] : []),
            'available_actions' => $this->available_actions ?? [],
        ];
    }

    private function taxCard(): ?array
    {
        $media = $this->getFirstMedia('tax_card');
        if ($media === null) {
            return null;
        }
        $url = url('/api/v1/admin/clients/'.$this->owner_client_id.'/workspaces/'.$this->id.'/tax-card');

        return [
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'view_url' => $url,
            'download_url' => $url.'?download=1',
        ];
    }
}
