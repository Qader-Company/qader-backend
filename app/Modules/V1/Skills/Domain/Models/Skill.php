<?php

namespace App\Modules\V1\Skills\Domain\Models;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    use Filterable;

    protected $fillable = ['name', 'is_active'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $skill): void {
            $skill->forceFill([
                'slug' => 'pending-'.Str::uuid(),
            ]);
        });

        static::created(function (self $skill): void {
            $skill->forceFill([
                'slug' => $skill->generatedSlug(),
            ])->saveQuietly();
        });

        static::updating(function (self $skill): void {
            $skill->forceFill([
                'slug' => $skill->generatedSlug(),
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

        return ($baseSlug !== '' ? $baseSlug : 'skill').$suffix;
    }
}
