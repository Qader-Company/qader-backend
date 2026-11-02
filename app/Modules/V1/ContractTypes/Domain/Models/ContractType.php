<?php

namespace App\Modules\V1\ContractTypes\Domain\Models;

use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringContractTypeEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\WorkDayEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    protected $fillable = [
        'type', 'is_active', 'name_en', 'name_ar', 'description_en', 'description_ar',
        'fee_type', 'fixed_amount', 'fixed_currency_id', 'percentage',
        'work_start_day', 'work_end_day', 'work_start_time', 'work_end_time',
        'work_type', 'work_mode', 'hybrid_work_days', 'work_location',
        'work_latitude', 'work_longitude',
    ];

    protected function casts(): array
    {
        return [
            'type' => HiringContractTypeEnum::class,
            'is_active' => 'boolean',
            'fee_type' => ContractFeeTypeEnum::class,
            'fixed_amount' => 'decimal:4',
            'percentage' => 'decimal:4',
            'work_start_day' => WorkDayEnum::class,
            'work_end_day' => WorkDayEnum::class,
            'work_type' => WorkTypeEnum::class,
            'work_mode' => WorkModeEnum::class,
            'hybrid_work_days' => 'array',
            'work_latitude' => 'decimal:7',
            'work_longitude' => 'decimal:7',
        ];
    }

    public function fixedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'fixed_currency_id');
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(ContractTypeBenefit::class)->orderBy('display_order');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(ContractTypeTerm::class)->orderBy('display_order');
    }

    public function feeOverrides(): HasMany
    {
        return $this->hasMany(ContractTypeFeeOverride::class);
    }
}
