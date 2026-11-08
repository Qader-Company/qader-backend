<?php

namespace App\Modules\V1\JobForms\Domain\Models;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobSectionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPostingSection extends Model
{
    protected $fillable = ['job_posting_id', 'section', 'content', 'display_order'];

    protected function casts(): array
    {
        return ['section' => JobSectionEnum::class];
    }

    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }
}
