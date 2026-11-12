<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferPartyEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Support\ApiMetadata\EnumLabel;
use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HiringRequestItemResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        $talent = $this->talent;
        $user = $talent?->user;
        $isAdmin = $this->isAdminRequest($request);
        $offer = $this->latestOffer === null
            ? null
            : (new HiringOfferResource($this->latestOffer))->resolve($request);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->value($this->status),
            'status_label' => EnumLabel::for($this->status, 'hiring_request_item_statuses'),
            'contract_type' => $this->value($this->contract_type),
            'contract_configuration' => $this->contractConfiguration($isAdmin),
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
                    'phone_country_code' => $talent?->phone_country_code,
                    'phone_number' => $talent?->phone_number,
                    'seniority_level' => $this->value($talent?->seniority_level),
                    'years_of_experience' => $talent?->years_of_experience,
                    'notice_period_days' => $talent?->notice_period_days,
                ] : []),
            ],
            'offer' => $offer,
            'offers' => HiringOfferResource::collection($this->whenLoaded('offers')),
            'interview' => $this->whenLoaded('interview', fn () => new HiringInterviewResource($this->interview)),
            'client_decision' => $this->value($this->client_decision),
            'client_decision_reason' => $this->client_decision_reason,
            'client_decided_at' => $this->client_decided_at,
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

    private function contractConfiguration(bool $isAdmin): ?array
    {
        $configuration = $this->contract_configuration_snapshot;
        if ($configuration === null || $isAdmin) {
            return $configuration;
        }

        $configuration['name'] = $this->localizedTranslation($configuration['name'] ?? null);
        $configuration['description'] = $this->localizedTranslation($configuration['description'] ?? null);
        $configuration['benefits'] = array_map(function (array $benefit): array {
            $benefit['name'] = $this->localizedTranslation($benefit['name'] ?? null);

            return $benefit;
        }, $configuration['benefits'] ?? []);
        $configuration['terms'] = array_map(function (array $term): array {
            $term['content'] = $this->localizedTranslation($term['content'] ?? null);

            return $term;
        }, $configuration['terms'] ?? []);

        return $configuration;
    }

    private function actions(Request $request): array
    {
        $uri = (string) $request->route()?->uri();
        $portal = str_contains($uri, '/admin/') ? 'admin' : (str_contains($uri, '/client/') ? 'client' : 'talent');
        $status = $this->status;
        $actions = ['view'];

        if ($portal === 'talent') {
            if ($status === HiringRequestItemStatusEnum::OFFER
                && $this->latestOffer?->status === HiringOfferStatusEnum::PENDING
                && $this->latestOffer?->proposed_by === HiringOfferPartyEnum::CLIENT) {
                array_push($actions, 'accept_offer', 'reject_offer', 'counter_offer');
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
            if ($this->decision_due_at !== null && $status === HiringRequestItemStatusEnum::INTERVIEW && $this->client_decision === null) {
                $actions[] = 'submit_decision';
            }

            return array_values(array_unique($actions));
        }

        $user = $request->user();
        $responsible = (int) $this->hiringRequest?->assigned_admin_id === (int) $user?->admin?->id;
        if (! $responsible) {
            return $actions;
        }
        if ($user?->can(AdminPermissionEnum::HIRING_REQUESTS_HANDLE->value)) {
            if ($status === HiringRequestItemStatusEnum::OFFER
                && (($this->latestOffer?->status === HiringOfferStatusEnum::PENDING
                    && $this->latestOffer?->proposed_by === HiringOfferPartyEnum::TALENT)
                    || $this->latestOffer?->status === HiringOfferStatusEnum::EXPIRED)) {
                $actions[] = 'send_offer';
            }
            if ($status === HiringRequestItemStatusEnum::INTERVIEW
                && ! in_array($this->interview?->status, [HiringInterviewStatusEnum::CONFIRMED, HiringInterviewStatusEnum::IN_PROGRESS, HiringInterviewStatusEnum::COMPLETED], true)) {
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
            if (
                $status === HiringRequestItemStatusEnum::OFFER
                && in_array($this->latestOffer?->status, [HiringOfferStatusEnum::PENDING, HiringOfferStatusEnum::EXPIRED], true)
            ) {
                $actions[] = 'respond_to_offer_on_behalf';
            }
            if ($status === HiringRequestItemStatusEnum::INTERVIEW
                && $this->interview?->status === HiringInterviewStatusEnum::AWAITING_RESPONSES) {
                $actions[] = 'respond_to_interview_on_behalf';
            }
            if ($this->decision_due_at !== null && in_array($status, [HiringRequestItemStatusEnum::INTERVIEW, HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::ACCEPTED], true)) {
                $actions[] = 'manage_decisions_on_behalf';
            }
        }

        return array_values(array_unique($actions));
    }

    private function isOperational(HiringRequestItemStatusEnum $status): bool
    {
        return ! in_array($status, [
            HiringRequestItemStatusEnum::PENDING,
            HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::CANCELLED,
            HiringRequestItemStatusEnum::COMPLETED,
        ], true);
    }
}
