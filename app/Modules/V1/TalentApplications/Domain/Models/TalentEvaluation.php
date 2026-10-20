<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalentEvaluation extends Model
{
    protected $fillable = [
        'application_id', 'evaluator_admin_id',
        'technical_skills_rating', 'communication_skills_rating',
        'problem_solving_rating', 'soft_skills_rating',
        'recommended_salary_min', 'recommended_salary_max', 'currency_id',
        'hr_report', 'technical_report', 'psychology_report',
    ];

    protected function casts(): array
    {
        return [
            'technical_skills_rating' => 'integer',
            'communication_skills_rating' => 'integer',
            'problem_solving_rating' => 'integer',
            'soft_skills_rating' => 'integer',
            'recommended_salary_min' => 'decimal:4',
            'recommended_salary_max' => 'decimal:4',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TalentApplication::class, 'application_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'evaluator_admin_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function tips(): HasMany
    {
        return $this->hasMany(EvaluationTip::class, 'evaluation_id')->orderBy('display_order');
    }

    public function averageRating(): float
    {
        return round((
            $this->technical_skills_rating
            + $this->communication_skills_rating
            + $this->problem_solving_rating
            + $this->soft_skills_rating
        ) / 4, 2);
    }
}
