<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\SeniorityLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'whatsapp_country_code',
    'whatsapp_number',
    'date_of_birth',
    'gender',
    'nationality_country_id',
    'residence_country_id',
    'residence_city_id',
    'job_title_id',
    'seniority_level',
    'years_of_experience',
    'headline',
    'bio',
    'linkedin_url',
    'github_url',
    'behance_url',
    'portfolio_url',
    'profile_completion_percentage',
    'profile_completion_details',
    'profile_completion_updated_at',
    'onboarding_step',
    'onboarding_completed_at',
])]
class Talent extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nationalityCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_country_id');
    }

    public function residenceCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'residence_country_id');
    }

    public function residenceCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'residence_city_id');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'talent_languages')
            ->withPivot('proficiency_level')
            ->withTimestamps();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'talent_skills')
            ->withPivot(['proficiency_level', 'years_of_experience', 'is_primary'])
            ->withTimestamps();
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'talent_tools')->withTimestamps();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(TalentExperience::class)->orderBy('display_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(TalentProject::class)->orderBy('display_order');
    }

    public function workPreference(): HasOne
    {
        return $this->hasOne(TalentWorkPreference::class);
    }

    public function workTypes(): HasMany
    {
        return $this->hasMany(TalentWorkType::class);
    }

    public function workModes(): HasMany
    {
        return $this->hasMany(TalentWorkMode::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TalentDocument::class);
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'seniority_level' => SeniorityLevel::class,
            'profile_completion_details' => 'array',
            'profile_completion_updated_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }
}
