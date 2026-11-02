<?php

namespace App\Modules\V1\ContractTypes\Presentation\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->value($this->type),
            'is_active' => (bool) $this->is_active,
            'name' => ['en' => $this->name_en, 'ar' => $this->name_ar],
            'description' => ['en' => $this->description_en, 'ar' => $this->description_ar],
            'fee' => [
                'type' => $this->value($this->fee_type),
                'fixed_amount' => $this->fixed_amount,
                'fixed_currency' => $this->fixedCurrency === null ? null : [
                    'id' => $this->fixedCurrency->id,
                    'code' => $this->fixedCurrency->code,
                    'symbol' => $this->fixedCurrency->symbol,
                ],
                'percentage' => $this->percentage,
            ],
            'benefits' => $this->benefits->map(fn ($benefit): array => [
                'id' => $benefit->id,
                'name' => ['en' => $benefit->name_en, 'ar' => $benefit->name_ar],
                'display_order' => $benefit->display_order,
            ]),
            'terms' => $this->terms->map(fn ($term): array => [
                'id' => $term->id,
                'content' => ['en' => $term->content_en, 'ar' => $term->content_ar],
                'display_order' => $term->display_order,
            ]),
            'fee_overrides' => $this->feeOverrides->map(fn ($override): array => [
                'id' => $override->id,
                'professional_field' => $override->professionalField === null ? null : [
                    'id' => $override->professionalField->id,
                    'name' => $override->professionalField->name,
                ],
                'min_employees' => $override->min_employees,
                'max_employees' => $override->max_employees,
                'fee_type' => $this->value($override->fee_type),
                'fixed_amount' => $override->fixed_amount,
                'fixed_currency' => $override->fixedCurrency === null ? null : [
                    'id' => $override->fixedCurrency->id,
                    'code' => $override->fixedCurrency->code,
                    'symbol' => $override->fixedCurrency->symbol,
                ],
                'percentage' => $override->percentage,
                'is_active' => (bool) $override->is_active,
            ]),
            'defaults' => [
                'work_start_day' => $this->value($this->work_start_day),
                'work_end_day' => $this->value($this->work_end_day),
                'work_start_time' => $this->work_start_time,
                'work_end_time' => $this->work_end_time,
                'work_hours' => $this->workHours(),
                'work_type' => $this->value($this->work_type),
                'work_mode' => $this->value($this->work_mode),
                'hybrid_work_days' => $this->hybrid_work_days,
                'work_location' => $this->work_location,
                'work_latitude' => $this->work_latitude,
                'work_longitude' => $this->work_longitude,
            ],
            'updated_at' => $this->updated_at,
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function workHours(): ?float
    {
        if ($this->work_start_time === null || $this->work_end_time === null) {
            return null;
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', (string) $this->work_start_time));
        [$endHour, $endMinute] = array_map('intval', explode(':', (string) $this->work_end_time));

        return round(((($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute)) / 60, 2);
    }
}
