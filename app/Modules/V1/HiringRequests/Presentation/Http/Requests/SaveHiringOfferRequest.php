<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveHiringOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salary_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'work_type' => ['required', Rule::enum(WorkTypeEnum::class)],
            'work_mode' => ['required', Rule::enum(WorkModeEnum::class)],
            'work_start_day' => ['required', Rule::enum(WorkDayEnum::class)],
            'work_end_day' => ['required', Rule::enum(WorkDayEnum::class)],
            'hybrid_work_days' => ['nullable', 'array', 'min:1'],
            'hybrid_work_days.*' => ['required', 'distinct', Rule::enum(WorkDayEnum::class)],
            'work_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'work_location' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $days = array_column(WorkDayEnum::cases(), 'value');
            $start = array_search($this->input('work_start_day'), $days, true);
            $end = array_search($this->input('work_end_day'), $days, true);
            $hybridDays = $this->input('hybrid_work_days', []);
            if ($start !== false && $end !== false && $end < $start) {
                $validator->errors()->add('work_end_day', 'The work end day must be on or after the work start day.');
            }
            if ($this->input('work_mode') === WorkModeEnum::HYBRID->value) {
                if ($hybridDays === []) {
                    $validator->errors()->add('hybrid_work_days', 'Hybrid work days are required for hybrid work mode.');
                }
                foreach ($hybridDays as $day) {
                    $position = array_search($day, $days, true);
                    if ($start !== false && $end !== false && ($position < $start || $position > $end)) {
                        $validator->errors()->add('hybrid_work_days', 'Every hybrid remote day must be within the selected work-day range.');
                        break;
                    }
                }
            } elseif ($hybridDays !== []) {
                $validator->errors()->add('hybrid_work_days', 'Hybrid work days are only allowed for hybrid work mode.');
            }
        }];
    }
}
