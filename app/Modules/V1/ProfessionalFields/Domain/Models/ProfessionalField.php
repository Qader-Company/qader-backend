<?php

namespace App\Modules\V1\ProfessionalFields\Domain\Models;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Tools\Domain\Models\Tool;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProfessionalField extends Model implements TranslatableContract
{
    use Filterable, Translatable;

    public array $translatedAttributes = ['name', 'description'];

    protected $fillable = ['is_active'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $professionalField): void {
            $professionalField->forceFill([
                'slug' => 'pending-'.Str::uuid(),
            ]);
        });

        static::created(function (self $professionalField): void {
            $professionalField->forceFill([
                'slug' => $professionalField->generatedSlug(),
            ])->saveQuietly();
        });

        static::saving(function (self $professionalField): void {
            if (! $professionalField->exists) {
                return;
            }

            $professionalField->forceFill([
                'slug' => $professionalField->generatedSlug(),
            ]);
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function jobTitles(): BelongsToMany
    {
        return $this->belongsToMany(
            JobTitle::class,
            'job_title_professional_field',
        );
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class);
    }

    public function formFields(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfessionalFormField::class,
            'professional_field_form_field',
        )->withTimestamps();
    }

    private function generatedSlug(): string
    {
        return $this->slugWithId('professional-field');
    }

    private function slugWithId(string $fallback): string
    {
        $suffix = '-'.$this->getKey();
        $englishName = $this->translate('en', false)?->name;
        $baseSlug = Str::slug($englishName ?? '');
        $baseSlug = Str::substr(
            $baseSlug,
            0,
            255 - Str::length($suffix),
        );
        $baseSlug = rtrim($baseSlug, '-');

        return ($baseSlug !== '' ? $baseSlug : $fallback).$suffix;
    }
}
