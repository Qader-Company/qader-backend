<?php

namespace App\Modules\V1\Cities\Domain\Models;

use App\Modules\V1\Countries\Domain\Models\Country;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model implements TranslatableContract
{
    use Filterable, Translatable;

    public array $translatedAttributes = ['name'];

    protected $fillable = ['country_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
