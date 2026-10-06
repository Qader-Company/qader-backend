<?php

namespace App\Modules\V1\Countries\Domain\Models;

use App\Modules\V1\Cities\Domain\Models\City;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model implements TranslatableContract
{
    use Filterable, Translatable;

    public array $translatedAttributes = ['name'];

    protected $fillable = ['code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
