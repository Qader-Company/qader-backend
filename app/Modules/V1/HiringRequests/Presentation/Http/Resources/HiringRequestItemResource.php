<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

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

        if ($offer !== null) {
            $offer['notice_period_days'] = $talent?->notice_period_days;
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->value($this->status),
            'contract_type' => $this->value($this->contract_type),
            'interview_required' => (bool) $this->interview_required,
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
}
