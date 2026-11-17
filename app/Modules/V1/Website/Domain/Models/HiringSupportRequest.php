<?php

namespace App\Modules\V1\Website\Domain\Models;

use App\Modules\V1\Countries\Domain\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringSupportRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone_country_code',
        'phone_number',
        'country_id',
        'description',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
