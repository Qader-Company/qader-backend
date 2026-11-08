<?php

namespace App\Modules\V1\ContractTypes\Presentation\Http\Resources;

use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientContractTypeResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->value($this->type),
            'is_active' => (bool) $this->is_active,
            'name' => $this->localizedValue($this->name_en, $this->name_ar),
            'fee' => [
                'fixed_amount' => $this->fixed_amount,
                'fixed_currency' => $this->fixedCurrency === null ? null : [
                    'id' => $this->fixedCurrency->id,
                    'code' => $this->fixedCurrency->code,
                    'symbol' => $this->fixedCurrency->symbol,
                ],
            ],
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
