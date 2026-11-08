<?php

namespace App\Modules\V1\ContractTypes\Presentation\Http\Requests;

use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateContractTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'array:en,ar'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'array:en,ar'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'description.ar' => ['nullable', 'string', 'max:5000'],
            'fee' => ['sometimes', 'required', 'array'],
            'fee.type' => ['required_with:fee', Rule::enum(ContractFeeTypeEnum::class)],
            'fee.fixed_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'fee.fixed_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'fee.percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
            'benefits' => ['sometimes', 'array', 'max:100'],
            'benefits.*.name' => ['required', 'array:en,ar'],
            'benefits.*.name.en' => ['required', 'string', 'max:255'],
            'benefits.*.name.ar' => ['nullable', 'string', 'max:255'],
            'benefits.*.display_order' => ['required', 'integer', 'min:1', 'distinct'],
            'terms' => ['sometimes', 'array', 'max:100'],
            'terms.*.content' => ['required', 'array:en,ar'],
            'terms.*.content.en' => ['required', 'string', 'max:10000'],
            'terms.*.content.ar' => ['nullable', 'string', 'max:10000'],
            'terms.*.display_order' => ['required', 'integer', 'min:1', 'distinct'],
            'fee_overrides' => ['sometimes', 'array', 'max:200'],
            'fee_overrides.*.professional_field_id' => ['required', 'integer', Rule::exists('professional_fields', 'id')->where('is_active', true)],
            'fee_overrides.*.min_employees' => ['required', 'integer', 'min:1'],
            'fee_overrides.*.max_employees' => ['required', 'integer', 'min:1'],
            'fee_overrides.*.fee_type' => ['required', Rule::enum(ContractFeeTypeEnum::class)],
            'fee_overrides.*.fixed_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'fee_overrides.*.fixed_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'fee_overrides.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
            'fee_overrides.*.is_active' => ['sometimes', 'boolean'],
            'defaults' => ['sometimes', 'required', 'array'],
            'defaults.work_start_day' => ['required_with:defaults', Rule::enum(WorkDayEnum::class)],
            'defaults.work_end_day' => ['required_with:defaults', Rule::enum(WorkDayEnum::class)],
            'defaults.work_start_time' => ['required_with:defaults', 'date_format:H:i'],
            'defaults.work_end_time' => ['required_with:defaults', 'date_format:H:i'],
            'defaults.work_type' => ['required_with:defaults', Rule::enum(WorkTypeEnum::class)],
            'defaults.work_mode' => ['required_with:defaults', Rule::enum(WorkModeEnum::class)],
            'defaults.hybrid_work_days' => ['nullable', 'array'],
            'defaults.hybrid_work_days.*' => ['required', 'distinct', Rule::enum(WorkDayEnum::class)],
            'defaults.work_location' => ['nullable', 'string', 'max:500'],
            'defaults.work_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'defaults.work_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['example' => ['en' => 'Managed Contract', 'ar' => 'عقد مُدار']],
            'description' => ['example' => [
                'en' => 'Qader manages the employment lifecycle.',
                'ar' => 'تدير قادر دورة التوظيف.',
            ]],
            'fee' => ['example' => [
                'type' => ContractFeeTypeEnum::PERCENTAGE->value,
                'fixed_amount' => null,
                'fixed_currency_id' => null,
                'percentage' => 5,
            ]],
            'benefits' => ['example' => [[
                'name' => ['en' => 'Managed support', 'ar' => 'دعم مُدار'],
                'display_order' => 1,
            ]]],
            'terms' => ['example' => [
                ['content' => ['en' => 'First contract term.', 'ar' => 'البند الأول للعقد.'], 'display_order' => 1],
                ['content' => ['en' => 'Second contract term.', 'ar' => 'البند الثاني للعقد.'], 'display_order' => 2],
            ]],
            'fee_overrides' => ['example' => [[
                'professional_field_id' => 1,
                'min_employees' => 11,
                'max_employees' => 50,
                'fee_type' => ContractFeeTypeEnum::PERCENTAGE->value,
                'fixed_amount' => null,
                'fixed_currency_id' => null,
                'percentage' => 4,
                'is_active' => true,
            ]]],
            'defaults' => ['example' => [
                'work_start_day' => WorkDayEnum::SUNDAY->value,
                'work_end_day' => WorkDayEnum::THURSDAY->value,
                'work_start_time' => '09:00',
                'work_end_time' => '17:00',
                'work_type' => WorkTypeEnum::FULL_TIME->value,
                'work_mode' => WorkModeEnum::HYBRID->value,
                'hybrid_work_days' => [WorkDayEnum::THURSDAY->value],
                'work_location' => 'Cairo, Egypt',
                'work_latitude' => 30.04442,
                'work_longitude' => 31.235712,
            ]],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->has('fee')) {
                $this->validateFee($validator, 'fee', $this->input('fee', []));
            }

            $overrides = $this->input('fee_overrides', []);
            foreach ($overrides as $index => $override) {
                if (($override['max_employees'] ?? 0) < ($override['min_employees'] ?? 1)) {
                    $validator->errors()->add("fee_overrides.{$index}.max_employees", 'The maximum employees must be greater than or equal to the minimum.');
                }
                $this->validateFee($validator, "fee_overrides.{$index}", [
                    'type' => $override['fee_type'] ?? null,
                    'fixed_amount' => $override['fixed_amount'] ?? null,
                    'fixed_currency_id' => $override['fixed_currency_id'] ?? null,
                    'percentage' => $override['percentage'] ?? null,
                ]);
            }
            $this->validateOverlaps($validator, $overrides);

            if ($this->has('defaults')) {
                $this->validateDefaults($validator, $this->input('defaults', []));
            }
        }];
    }

    private function validateFee(Validator $validator, string $path, array $fee): void
    {
        $type = $fee['type'] ?? null;
        $usesFixed = in_array($type, [ContractFeeTypeEnum::FIXED->value, ContractFeeTypeEnum::FIXED_PERCENTAGE->value], true);
        $usesPercentage = in_array($type, [ContractFeeTypeEnum::PERCENTAGE->value, ContractFeeTypeEnum::FIXED_PERCENTAGE->value], true);

        if ($usesFixed && (! isset($fee['fixed_amount']) || ! isset($fee['fixed_currency_id']))) {
            $validator->errors()->add("{$path}.fixed_amount", 'Fixed amount and currency are required for this fee type.');
        }
        if (! $usesFixed && (isset($fee['fixed_amount']) || isset($fee['fixed_currency_id']))) {
            $validator->errors()->add("{$path}.fixed_amount", 'Fixed amount is not allowed for this fee type.');
        }
        if ($usesPercentage && ! isset($fee['percentage'])) {
            $validator->errors()->add("{$path}.percentage", 'Percentage is required for this fee type.');
        }
        if (! $usesPercentage && isset($fee['percentage'])) {
            $validator->errors()->add("{$path}.percentage", 'Percentage is not allowed for this fee type.');
        }
    }

    private function validateOverlaps(Validator $validator, array $overrides): void
    {
        foreach ($overrides as $leftIndex => $left) {
            if (($left['is_active'] ?? true) !== true) {
                continue;
            }
            foreach (array_slice($overrides, $leftIndex + 1, null, true) as $rightIndex => $right) {
                if (($right['is_active'] ?? true) !== true
                    || ($left['professional_field_id'] ?? null) !== ($right['professional_field_id'] ?? null)) {
                    continue;
                }
                if (($left['min_employees'] ?? 0) <= ($right['max_employees'] ?? -1)
                    && ($right['min_employees'] ?? 0) <= ($left['max_employees'] ?? -1)) {
                    $validator->errors()->add("fee_overrides.{$rightIndex}.min_employees", 'Active employee ranges for the same professional field may not overlap.');
                }
            }
        }
    }

    private function validateDefaults(Validator $validator, array $defaults): void
    {
        $start = strtotime((string) ($defaults['work_start_time'] ?? ''));
        $end = strtotime((string) ($defaults['work_end_time'] ?? ''));
        if ($start !== false && $end !== false && $end <= $start) {
            $validator->errors()->add('defaults.work_end_time', 'The work end time must be after the start time.');
        } elseif ($start !== false && $end !== false && (($end - $start) % 3600 !== 0 || ($end - $start) > 86400)) {
            $validator->errors()->add('defaults.work_end_time', 'Working hours must resolve to a whole number between 1 and 24.');
        }

        $days = array_column(WorkDayEnum::cases(), 'value');
        $startDay = array_search($defaults['work_start_day'] ?? null, $days, true);
        $endDay = array_search($defaults['work_end_day'] ?? null, $days, true);
        if ($startDay !== false && $endDay !== false && $endDay < $startDay) {
            $validator->errors()->add('defaults.work_end_day', 'The work end day must be on or after the start day.');
        }

        $hybridDays = $defaults['hybrid_work_days'] ?? [];
        if (($defaults['work_mode'] ?? null) === WorkModeEnum::HYBRID->value) {
            if ($hybridDays === []) {
                $validator->errors()->add('defaults.hybrid_work_days', 'Hybrid work days are required for hybrid mode.');
            }
        } elseif ($hybridDays !== []) {
            $validator->errors()->add('defaults.hybrid_work_days', 'Hybrid work days are only allowed for hybrid mode.');
        }

        foreach ($hybridDays as $day) {
            $position = array_search($day, $days, true);
            if ($startDay !== false && $endDay !== false && ($position < $startDay || $position > $endDay)) {
                $validator->errors()->add('defaults.hybrid_work_days', 'Every hybrid day must be within the work-day range.');
                break;
            }
        }

        $hasLocation = filled($defaults['work_location'] ?? null);
        $hasLatitude = isset($defaults['work_latitude']);
        $hasLongitude = isset($defaults['work_longitude']);
        if ($hasLatitude !== $hasLongitude) {
            $validator->errors()->add('defaults.work_latitude', 'Latitude and longitude must be provided together.');
        }
        if (in_array($defaults['work_mode'] ?? null, [WorkModeEnum::ONSITE->value, WorkModeEnum::HYBRID->value], true)
            && (! $hasLocation || ! $hasLatitude || ! $hasLongitude)) {
            $validator->errors()->add('defaults.work_location', 'Location and coordinates are required for onsite and hybrid work.');
        }
    }
}
