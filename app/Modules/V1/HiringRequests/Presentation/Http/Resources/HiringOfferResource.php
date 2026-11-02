<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HiringOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposed_by' => $this->value($this->proposed_by),
            'salary_amount' => $this->salary_amount,
            'currency' => $this->currency === null ? null : [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
                'name' => $this->currency->name,
            ],
            'work_type' => $this->value($this->work_type),
            'work_mode' => $this->value($this->work_mode),
            'work_start_day' => $this->value($this->work_start_day),
            'work_end_day' => $this->value($this->work_end_day),
            'hybrid_work_days' => $this->hybrid_work_days,
            'work_hours' => $this->work_hours,
            'notice_period_days' => $this->notice_period_days,
            'work_location' => $this->work_location,
            'status' => $this->value($this->status),
            'sent_at' => $this->sent_at,
            'response_due_at' => $this->response_due_at,
            'is_overdue' => $this->status === HiringOfferStatusEnum::PENDING
                && $this->response_due_at !== null
                && $this->response_due_at->isPast(),
            'created_at' => $this->created_at,
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
