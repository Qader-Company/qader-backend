<?php

namespace App\Modules\V1\Website\Domain\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingFaq extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    public array $translatedAttributes = ['question', 'answer'];

    protected $fillable = ['display_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
