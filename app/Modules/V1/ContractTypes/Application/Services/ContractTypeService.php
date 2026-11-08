<?php

namespace App\Modules\V1\ContractTypes\Application\Services;

use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractTypeService
{
    private const RELATIONS = [
        'fixedCurrency', 'benefits', 'terms',
        'feeOverrides.professionalField.translations', 'feeOverrides.fixedCurrency',
    ];

    public function all(): Collection
    {
        return ContractType::query()->with(self::RELATIONS)->orderBy('id')->get();
    }

    public function show(ContractType $contractType): ContractType
    {
        return $contractType->load(self::RELATIONS);
    }

    public function update(ContractType $contractType, array $data): ContractType
    {
        return DB::transaction(function () use ($contractType, $data): ContractType {
            $attributes = [];
            if (isset($data['name'])) {
                $attributes += ['name_en' => $data['name']['en'], 'name_ar' => $data['name']['ar']];
            }
            if (array_key_exists('description', $data)) {
                $attributes += [
                    'description_en' => $data['description']['en'] ?? null,
                    'description_ar' => $data['description']['ar'] ?? null,
                ];
            }
            if (isset($data['fee'])) {
                $attributes += [
                    'fee_type' => $data['fee']['type'],
                    'fixed_amount' => $data['fee']['fixed_amount'] ?? null,
                    'fixed_currency_id' => $data['fee']['fixed_currency_id'] ?? null,
                    'percentage' => $data['fee']['percentage'] ?? null,
                ];
            }
            if (isset($data['defaults'])) {
                $attributes += [
                    ...$data['defaults'],
                    'hybrid_work_days' => $data['defaults']['hybrid_work_days'] ?? null,
                    'work_location' => $data['defaults']['work_location'] ?? null,
                    'work_latitude' => $data['defaults']['work_latitude'] ?? null,
                    'work_longitude' => $data['defaults']['work_longitude'] ?? null,
                ];
            }
            if ($attributes !== []) {
                $contractType->update($attributes);
            }

            if (array_key_exists('benefits', $data)) {
                $contractType->benefits()->delete();
                $contractType->benefits()->createMany(array_map(fn (array $benefit): array => [
                    'name_en' => $benefit['name']['en'],
                    'name_ar' => $benefit['name']['ar'] ?? null,
                    'display_order' => $benefit['display_order'],
                ], $data['benefits']));
            }
            if (array_key_exists('terms', $data)) {
                $contractType->terms()->delete();
                $contractType->terms()->createMany(array_map(fn (array $term): array => [
                    'content_en' => $term['content']['en'],
                    'content_ar' => $term['content']['ar'] ?? null,
                    'display_order' => $term['display_order'],
                ], $data['terms']));
            }
            if (array_key_exists('fee_overrides', $data)) {
                $contractType->feeOverrides()->delete();
                $contractType->feeOverrides()->createMany(array_map(fn (array $override): array => [
                    ...$override,
                    'is_active' => $override['is_active'] ?? true,
                ], $data['fee_overrides']));
            }

            return $contractType->refresh()->load(self::RELATIONS);
        });
    }

    public function setStatus(ContractType $contractType, bool $active): ContractType
    {
        if ($active) {
            $this->ensureReady($contractType);
        }
        $contractType->update(['is_active' => $active]);

        return $this->show($contractType->refresh());
    }

    private function ensureReady(ContractType $contractType): void
    {
        $usesFixed = in_array($contractType->fee_type, [ContractFeeTypeEnum::FIXED, ContractFeeTypeEnum::FIXED_PERCENTAGE], true);
        $usesPercentage = in_array($contractType->fee_type, [ContractFeeTypeEnum::PERCENTAGE, ContractFeeTypeEnum::FIXED_PERCENTAGE], true);
        $missing = $contractType->work_start_day === null || $contractType->work_end_day === null
            || $contractType->work_start_time === null || $contractType->work_end_time === null
            || $contractType->work_type === null || $contractType->work_mode === null
            || ($usesFixed && ($contractType->fixed_amount === null || $contractType->fixed_currency_id === null))
            || ($usesPercentage && $contractType->percentage === null);
        if (in_array($contractType->work_mode, [WorkModeEnum::ONSITE, WorkModeEnum::HYBRID], true)) {
            $missing = $missing || blank($contractType->work_location)
                || $contractType->work_latitude === null || $contractType->work_longitude === null;
        }
        if ($missing) {
            throw ValidationException::withMessages(['contract_type' => ['Complete the fee and work defaults before activating this contract type.']]);
        }
    }
}
