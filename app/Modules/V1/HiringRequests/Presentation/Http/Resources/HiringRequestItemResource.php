<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferPartyEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HiringRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $talent = $this->talent;
        $user = $talent?->user;
        $isAdmin = str_contains((string) $request->route()?->uri(), '/admin/');
        $offer = $this->latestOffer === null
            ? null
            : (new HiringOfferResource($this->latestOffer))->resolve($request);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->value($this->status),
            'contract_type' => $this->value($this->contract_type),
            'contract_configuration' => $this->contract_configuration_snapshot,
            'interview_required' => (bool) $this->interview_required,
            'company' => $this->hiringRequest?->workspace === null ? null : [
                'id' => $this->hiringRequest->workspace->id,
                'name' => $this->hiringRequest->workspace->name,
                'logo_url' => $this->hiringRequest->workspace->getFirstMediaUrl('logo') ?: null,
            ],
            'talent' => [
                'id' => $talent?->id,
                'talent_code' => $talent?->talent_code,
                'job_title' => $this->jobTitle === null ? null : [
                    'id' => $this->jobTitle->id,
                    'name' => $this->jobTitle->name,
                ],
                ...($isAdmin ? [
                    'full_name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
                    'email' => $user?->email,
                ] : []),
            ],
            'offer' => $offer,
            'offers' => HiringOfferResource::collection($this->whenLoaded('offers')),
            'interview' => $this->whenLoaded('interview', fn () => new HiringInterviewResource($this->interview)),
            'client_decision' => $this->value($this->client_decision),
            'client_decision_reason' => $this->client_decision_reason,
            'client_decided_at' => $this->client_decided_at,
            'talent_decision' => $this->value($this->talent_decision),
            'talent_decision_reason' => $this->talent_decision_reason,
            'talent_decided_at' => $this->talent_decided_at,
            'decision_due_at' => $this->decision_due_at,
            'available_actions' => $this->available_actions ?? $this->actions($request),
            'rejection_reason' => $this->rejection_reason,
            'hold_reason' => $this->hold_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'updated_at' => $this->updated_at,
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function actions(Request $request): array
    {
        $uri = (string) $request->route()?->uri();
        $portal = str_contains($uri, '/admin/') ? 'admin' : (str_contains($uri, '/client/') ? 'client' : 'talent');
        $status = $this->status;
        $actions = ['view'];

        if ($portal === 'talent') {
            if ($status === HiringRequestItemStatusEnum::AWAITING_TALENT_RESPONSE
                && $this->latestOffer?->status === HiringOfferStatusEnum::PENDING
                && $this->latestOffer?->proposed_by === HiringOfferPartyEnum::CLIENT) {
                array_push($actions, 'accept_offer', 'reject_offer', 'counter_offer');
            }
            if ($this->decision_due_at !== null && in_array($status, [HiringRequestItemStatusEnum::AWAITING_DECISIONS, HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::READY_TO_PROCEED], true)) {
                $actions[] = $this->talent_decision === null ? 'submit_decision' : 'cancel_decision';
            }

            return $actions;
        }

        if ($portal === 'client') {
            if ($this->isOperational($status)) {
                array_push($actions, 'hold', 'cancel');
            }
            if ($status === HiringRequestItemStatusEnum::ON_HOLD) {
                array_push($actions, 'resume', 'cancel');
            }
            if ($this->decision_due_at !== null && in_array($status, [HiringRequestItemStatusEnum::AWAITING_DECISIONS, HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::READY_TO_PROCEED], true)) {
                $actions[] = $this->client_decision === null ? 'submit_decision' : 'cancel_decision';
            }

            return array_values(array_unique($actions));
        }

        $user = $request->user();
        $responsible = (int) $this->hiringRequest?->assigned_admin_id === (int) $user?->admin?->id;
        if (! $responsible) {
            return $actions;
        }
        if ($user?->can(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->value)) {
            if ($status === HiringRequestItemStatusEnum::NEGOTIATING || $this->latestOffer?->status === HiringOfferStatusEnum::EXPIRED) {
                $actions[] = 'send_offer';
            }
            if ($status === HiringRequestItemStatusEnum::AWAITING_INTERVIEW_SLOTS) {
                $actions[] = 'schedule_interview';
            }
            if ($this->isOperational($status)) {
                array_push($actions, 'hold', 'cancel');
            }
            if ($status === HiringRequestItemStatusEnum::ON_HOLD) {
                array_push($actions, 'resume', 'cancel');
            }
        }
        if ($user?->can(AdminPermissionEnum::HIRING_REQUESTS_RESPOND_ON_BEHALF->value)) {
            if (in_array($this->latestOffer?->status, [HiringOfferStatusEnum::PENDING, HiringOfferStatusEnum::EXPIRED], true)) {
                $actions[] = 'respond_to_offer_on_behalf';
            }
            if ($this->decision_due_at !== null && in_array($status, [HiringRequestItemStatusEnum::AWAITING_DECISIONS, HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::READY_TO_PROCEED], true)) {
                $actions[] = 'manage_decisions_on_behalf';
            }
        }

        return array_values(array_unique($actions));
    }

    private function isOperational(HiringRequestItemStatusEnum $status): bool
    {
        return ! in_array($status, [
            HiringRequestItemStatusEnum::DRAFT, HiringRequestItemStatusEnum::PENDING,
            HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::CANCELLED,
            HiringRequestItemStatusEnum::READY_FOR_CONTRACT, HiringRequestItemStatusEnum::COMPLETED,
        ], true);
    }
}
