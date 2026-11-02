<?php

namespace App\Modules\V1\ContractTypes\Domain\Models;

use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTypeFeeOverride extends Model
{
    protected $fillable = [
        'contract_type_id', 'professional_field_id', 'min_employees', 'max_employees',
        'fee_type', 'fixed_amount', 'fixed_currency_id', 'percentage', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fee_type' => ContractFeeTypeEnum::class,
            'fixed_amount' => 'decimal:4',
            'percentage' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function professionalField(): BelongsTo
    {
        return $this->belongsTo(ProfessionalField::class);
    }

    public function fixedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'fixed_currency_id');
    }
}
