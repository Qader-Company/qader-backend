<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\ContractTypes\Domain\Models\ContractTypeFeeOverride;
use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use BackedEnum;
use Illuminate\Validation\ValidationException;

class ContractConfigurationResolver
{
    public function resolve(
        ContractType $contractType,
        Talent $talent,
        Workspace $workspace,
        float $salary,
        string $attributePrefix = '',
    ): array {
        if (! $contractType->is_active) {
            $this->fail($attributePrefix, 'contract_type', 'hiring.contract_type_inactive');
        }
        if ($talent->expected_salary_min === null || $talent->expected_salary_max === null || $talent->expected_currency_id === null) {
            $this->fail($attributePrefix, 'salary_amount', 'hiring.talent_salary_incomplete');
        }
        if ($salary < (float) $talent->expected_salary_min || $salary > (float) $talent->expected_salary_max) {
            $this->fail($attributePrefix, 'salary_amount', 'hiring.salary_outside_talent_range');
        }

        $requiredDefaults = [
            $contractType->work_start_day, $contractType->work_end_day,
            $contractType->work_start_time, $contractType->work_end_time,
            $contractType->work_type, $contractType->work_mode,
        ];
        if (in_array(null, $requiredDefaults, true)) {
            $this->fail($attributePrefix, 'contract_type', 'hiring.contract_type_incomplete');
        }

        $workHours = $this->workHours((string) $contractType->work_start_time, (string) $contractType->work_end_time);
        if ($workHours === null) {
            $this->fail($attributePrefix, 'contract_type', 'hiring.contract_type_invalid_hours');
        }

        $override = $this->matchingOverride($contractType, $talent, $workspace);
        $fee = $this->fixedFee($contractType, $override, $attributePrefix);
        $snapshot = [
            'type' => $this->value($contractType->type),
            'name' => ['en' => $contractType->name_en, 'ar' => $contractType->name_ar],
            'description' => ['en' => $contractType->description_en, 'ar' => $contractType->description_ar],
            'benefits' => $contractType->benefits->map(fn ($item): array => [
                'name' => ['en' => $item->name_en, 'ar' => $item->name_ar],
                'display_order' => $item->display_order,
            ])->values()->all(),
            'terms' => $contractType->terms->map(fn ($item): array => [
                'content' => ['en' => $item->content_en, 'ar' => $item->content_ar],
                'display_order' => $item->display_order,
            ])->values()->all(),
            'fee' => $fee,
        ];

        return [
            'contract_type_id' => $contractType->id,
            'snapshot' => $snapshot,
            'offer' => [
                'salary_amount' => $salary,
                'currency_id' => $talent->expected_currency_id,
                'work_type' => $this->value($contractType->work_type),
                'work_mode' => $this->value($contractType->work_mode),
                'work_start_day' => $this->value($contractType->work_start_day),
                'work_end_day' => $this->value($contractType->work_end_day),
                'hybrid_work_days' => $contractType->hybrid_work_days,
                'work_hours' => $workHours,
                'notice_period_days' => $talent->notice_period_days,
                'work_location' => $contractType->work_location,
                'work_latitude' => $contractType->work_latitude,
                'work_longitude' => $contractType->work_longitude,
            ],
            'fee' => $fee,
        ];
    }

    private function matchingOverride(ContractType $contractType, Talent $talent, Workspace $workspace): ?ContractTypeFeeOverride
    {
        [$minimum, $maximum] = $this->workspaceBand($this->value($workspace->size_band));
        if ($maximum === null || $talent->professional_field_id === null) {
            return null;
        }

        return $contractType->feeOverrides
            ->first(fn (ContractTypeFeeOverride $override): bool => $override->is_active
                && (int) $override->professional_field_id === (int) $talent->professional_field_id
                && $override->min_employees <= $minimum
                && $override->max_employees >= $maximum);
    }

    private function workspaceBand(?string $band): array
    {
        return match ($band) {
            '1-10' => [1, 10],
            '11-50' => [11, 50],
            '51-200' => [51, 200],
            '201-500' => [201, 500],
            '500+' => [500, null],
            default => [0, null],
        };
    }

    private function fixedFee(
        ContractType $contractType,
        ?ContractTypeFeeOverride $override,
        string $attributePrefix,
    ): array {
        $source = $override ?? $contractType;
        $type = $this->value($source->fee_type);

        if ($type !== ContractFeeTypeEnum::FIXED->value
            || $source->fixed_amount === null
            || $source->fixed_currency_id === null) {
            $this->fail($attributePrefix, 'contract_type', 'hiring.contract_fixed_fee_incomplete');
        }

        return [
            'source' => $override === null ? 'default' : 'override',
            'override_id' => $override?->id,
            'type' => $type,
            'fixed_amount' => $source->fixed_amount,
            'fixed_currency_id' => $source->fixed_currency_id,
        ];
    }

    private function fail(string $prefix, string $attribute, string $translationKey): never
    {
        $key = $prefix === '' ? $attribute : "{$prefix}.{$attribute}";

        throw ValidationException::withMessages([$key => [__($translationKey)]]);
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function workHours(string $start, string $end): ?int
    {
        $startParts = array_map('intval', explode(':', $start));
        $endParts = array_map('intval', explode(':', $end));
        if (count($startParts) < 2 || count($endParts) < 2) {
            return null;
        }
        $minutes = (($endParts[0] * 60) + $endParts[1]) - (($startParts[0] * 60) + $startParts[1]);

        return $minutes > 0 && $minutes <= 1440 && $minutes % 60 === 0
            ? intdiv($minutes, 60)
            : null;
    }
}
