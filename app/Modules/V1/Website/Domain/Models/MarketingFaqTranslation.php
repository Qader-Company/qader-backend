<?php

namespace App\Modules\V1\Website\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingFaqTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['question', 'answer'];

    public function marketingFaq(): BelongsTo
    {
        return $this->belongsTo(MarketingFaq::class);
    }
}
