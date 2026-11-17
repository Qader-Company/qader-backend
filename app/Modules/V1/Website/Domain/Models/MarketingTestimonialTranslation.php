<?php

namespace App\Modules\V1\Website\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingTestimonialTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'role', 'quote'];

    public function marketingTestimonial(): BelongsTo
    {
        return $this->belongsTo(MarketingTestimonial::class);
    }
}
