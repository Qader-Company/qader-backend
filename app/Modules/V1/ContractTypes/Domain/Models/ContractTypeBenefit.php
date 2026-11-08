<?php

namespace App\Modules\V1\ContractTypes\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTypeBenefit extends Model
{
    protected $fillable = ['contract_type_id', 'name_en', 'name_ar', 'display_order'];

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }
}
