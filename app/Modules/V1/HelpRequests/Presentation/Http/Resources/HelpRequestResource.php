<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Resources;

use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestContactMethodEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminSummaryResource;
use App\Support\ApiMetadata\EnumLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = str_contains((string) $request->route()?->uri(), '/admin/');
        $clientUser = $this->createdByMember?->client?->user;
        $isWhatsapp = $this->contact_method === HelpRequestContactMethodEnum::WHATSAPP;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            // Figma "Request Title" = the selected help type name.
            'title' => EnumLabel::for($this->help_type, 'help_request_types'),
            'help_type' => $this->help_type?->value,
            'help_type_label' => EnumLabel::for($this->help_type, 'help_request_types'),
            'description' => $this->description,
            'contact' => [
                'method' => $this->contact_method?->value,
                'method_label' => EnumLabel::for($this->contact_method, 'help_request_contact_methods'),
                'value' => $isWhatsapp
                    ? trim(($this->contact_phone_country_code ?? '').' '.($this->contact_phone_number ?? ''))
                    : $this->contact_email,
                'email' => $this->contact_email,
                'phone_country_code' => $this->contact_phone_country_code,
                'phone_number' => $this->contact_phone_number,
            ],
            'status' => $this->status?->value,
            'status_label' => EnumLabel::for($this->status, 'help_request_statuses'),
            'workspace' => $this->relationLoaded('workspace') && $this->workspace !== null ? [
                'id' => $this->workspace->id,
                'name' => $this->workspace->name,
            ] : null,
            ...($isAdmin ? [
                'client' => $clientUser === null ? null : [
                    'id' => $this->createdByMember->client->id,
                    'full_name' => trim($clientUser->first_name.' '.$clientUser->last_name),
                    'email' => $clientUser->email,
                ],
                'assigned_admin' => $this->assignedAdmin === null ? null : new AdminSummaryResource($this->assignedAdmin),
                'started_at' => $this->started_at,
                'closure' => $this->closure_outcome === null ? null : [
                    'outcome' => $this->closure_outcome->value,
                    'outcome_label' => EnumLabel::for($this->closure_outcome, 'help_request_closure_outcomes'),
                    'details' => $this->closure_details,
                    'hiring_request' => $this->relationLoaded('resultingHiringRequest') && $this->resultingHiringRequest !== null ? [
                        'id' => $this->resultingHiringRequest->id,
                        'reference' => $this->resultingHiringRequest->reference,
                    ] : null,
                    'closed_by' => $this->relationLoaded('closedByAdmin') && $this->closedByAdmin !== null
                        ? new AdminSummaryResource($this->closedByAdmin)
                        : null,
                    'closed_at' => $this->closed_at,
                ],
                'notes_count' => (int) ($this->notes_count ?? 0),
                'notes' => HelpRequestNoteResource::collection($this->whenLoaded('notes')),
                'available_actions' => $this->available_actions ?? [],
            ] : []),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
