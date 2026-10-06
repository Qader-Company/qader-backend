<?php

namespace App\Modules\V1\Talents\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TalentProject extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('images')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
            ]);
    }
}
