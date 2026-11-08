<?php

namespace App\Modules\V1\ContractTypes\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTypeTerm extends Model
{
    protected $fillable = ['contract_type_id', 'content_en', 'content_ar', 'display_order'];

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }
}
