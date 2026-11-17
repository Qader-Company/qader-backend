<?php

namespace App\Modules\V1\Website\Domain\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MarketingTestimonial extends Model implements HasMedia, TranslatableContract
{
    use InteractsWithMedia, SoftDeletes, Translatable;

    public array $translatedAttributes = ['name', 'role', 'quote'];

    protected $fillable = ['display_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }
}
