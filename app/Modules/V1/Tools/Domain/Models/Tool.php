<?php

namespace App\Modules\V1\Tools\Domain\Models;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tool extends Model
{
    use Filterable;

    protected $fillable = ['name', 'is_active'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tool): void {
            $tool->forceFill([
                'slug' => 'pending-'.Str::uuid(),
            ]);
        });

        static::created(function (self $tool): void {
            $tool->forceFill([
                'slug' => $tool->generatedSlug(),
            ])->saveQuietly();
        });

        static::updating(function (self $tool): void {
            $tool->forceFill([
                'slug' => $tool->generatedSlug(),
            ]);
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function professionalFields(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalField::class);
    }

    private function generatedSlug(): string
    {
        $suffix = '-'.$this->getKey();
        $baseSlug = Str::slug($this->name);
        $baseSlug = Str::substr(
            $baseSlug,
            0,
            255 - Str::length($suffix),
        );
        $baseSlug = rtrim($baseSlug, '-');

        return ($baseSlug !== '' ? $baseSlug : 'tool').$suffix;
    }
}
