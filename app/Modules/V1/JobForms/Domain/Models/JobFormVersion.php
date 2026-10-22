<?php

namespace App\Modules\V1\JobForms\Domain\Models;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobFormVersionStatusEnum;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalFormField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobFormVersion extends Model
{
    protected $fillable = ['job_posting_id', 'version_number', 'status', 'job_details_snapshot', 'identity_fields_snapshot', 'published_at'];

    protected function casts(): array
    {
        return ['status' => JobFormVersionStatusEnum::class, 'job_details_snapshot' => 'array', 'identity_fields_snapshot' => 'array', 'published_at' => 'datetime'];
    }

    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalFormField::class, 'job_form_fields', 'form_version_id')->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(JobFormSubmission::class, 'form_version_id');
    }
}
