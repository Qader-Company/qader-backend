<?php

namespace App\Modules\V1\Website\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MarketingPartner extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = ['name', 'url', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']);
    }
}
