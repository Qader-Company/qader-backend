<?php

namespace App\Modules\V1\Talents\Domain\Models;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Domain\ValueObjects\GenderEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\ReligionEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'user_id',
    'phone_country_code',
    'phone_number',
    'whatsapp_country_code',
    'whatsapp_number',
    'date_of_birth',
    'gender',
    'religion',
    'nationality_country_id',
    'residence_country_id',
    'residence_city_id',
    'professional_field_id',
    'job_title_id',
    'seniority_level',
    'years_of_experience',
    'professional_summary',
    'linkedin_url',
    'github_url',
    'behance_url',
    'portfolio_url',
    'profile_completion_percentage',
    'profile_completion_details',
    'profile_completion_updated_at',
    'onboarding_step',
    'onboarding_completed_at',
    'willing_to_relocate',
    'notice_period_days',
    'current_salary',
    'current_currency_id',
    'expected_salary_min',
    'expected_salary_max',
    'expected_currency_id',
])]
class Talent extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'talents';

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

    public function professionalField(): BelongsTo
    {
        return $this->belongsTo(ProfessionalField::class);
    }

    public function currentCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'current_currency_id');
    }

    public function expectedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'expected_currency_id');
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

    public function workTypes(): HasMany
    {
        return $this->hasMany(TalentWorkType::class);
    }

    public function workModes(): HasMany
    {
        return $this->hasMany(TalentWorkMode::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('cv')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => GenderEnum::class,
            'religion' => ReligionEnum::class,
            'seniority_level' => SeniorityLevelEnum::class,
            'onboarding_step' => OnboardingStepEnum::class,
            'profile_completion_details' => 'array',
            'profile_completion_updated_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'willing_to_relocate' => 'boolean',
            'current_salary' => 'decimal:2',
            'expected_salary_min' => 'decimal:2',
            'expected_salary_max' => 'decimal:2',
        ];
    }
}
