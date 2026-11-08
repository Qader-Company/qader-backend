<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewEvaluation extends Model
{
    protected $fillable = [
        'interview_id', 'evaluator_admin_id',
        'technical_skills_rating', 'communication_skills_rating',
        'problem_solving_rating', 'soft_skills_rating',
        'hr_report', 'technical_report', 'psychology_report', 'final_feedback',
    ];

    protected function casts(): array
    {
        return [
            'technical_skills_rating' => 'integer',
            'communication_skills_rating' => 'integer',
            'problem_solving_rating' => 'integer',
            'soft_skills_rating' => 'integer',
        ];
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'evaluator_admin_id');
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

    public function overallScore(): int
    {
        return (int) round(($this->averageRating() / 5) * 100);
    }
}
