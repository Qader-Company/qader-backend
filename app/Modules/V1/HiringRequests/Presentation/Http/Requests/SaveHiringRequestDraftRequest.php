<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Requests;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveHiringRequestDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.talent_id' => ['required', 'integer', 'distinct', 'exists:talents,id'],
            'items.*.contract_type' => ['required', Rule::enum(HiringContractTypeEnum::class)],
            'items.*.interview_required' => ['required', 'boolean'],
            'items.*.offer' => ['required', 'array'],
            'items.*.offer.salary_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'items.*.offer.currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'items.*.offer.work_type' => ['required', Rule::enum(WorkTypeEnum::class)],
            'items.*.offer.work_mode' => ['required', Rule::enum(WorkModeEnum::class)],
            'items.*.offer.work_start_day' => ['required', Rule::enum(WorkDayEnum::class)],
            'items.*.offer.work_end_day' => ['required', Rule::enum(WorkDayEnum::class)],
            'items.*.offer.hybrid_work_days' => ['nullable', 'array', 'min:1'],
            'items.*.offer.hybrid_work_days.*' => ['required', 'distinct', Rule::enum(WorkDayEnum::class)],
            'items.*.offer.work_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'items.*.offer.notice_period_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'items.*.offer.work_location' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $days = array_column(WorkDayEnum::cases(), 'value');

            foreach ($this->input('items', []) as $index => $item) {
                $offer = $item['offer'] ?? [];
                $mode = $offer['work_mode'] ?? null;
                $hybridDays = $offer['hybrid_work_days'] ?? [];
                $start = array_search($offer['work_start_day'] ?? null, $days, true);
                $end = array_search($offer['work_end_day'] ?? null, $days, true);

                if ($start !== false && $end !== false && $end < $start) {
                    $validator->errors()->add("items.{$index}.offer.work_end_day", 'The work end day must be on or after the work start day.');
                }

                if ($mode === WorkModeEnum::HYBRID->value) {
                    if ($hybridDays === []) {
                        $validator->errors()->add("items.{$index}.offer.hybrid_work_days", 'Hybrid work days are required for hybrid work mode.');
                    }

                    foreach ($hybridDays as $day) {
                        $position = array_search($day, $days, true);
                        if ($start !== false && $end !== false && ($position < $start || $position > $end)) {
                            $validator->errors()->add("items.{$index}.offer.hybrid_work_days", 'Every hybrid remote day must be within the selected work-day range.');
                            break;
                        }
                    }
                } elseif ($hybridDays !== []) {
                    $validator->errors()->add("items.{$index}.offer.hybrid_work_days", 'Hybrid work days are only allowed for hybrid work mode.');
                }
            }
        }];
    }
}
