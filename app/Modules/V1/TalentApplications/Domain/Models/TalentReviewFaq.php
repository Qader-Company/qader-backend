<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class TalentReviewFaq extends Model implements TranslatableContract
{
    use Translatable;

    public array $translatedAttributes = ['question', 'answer'];

    protected $fillable = ['screen_states', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'screen_states' => 'array',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
