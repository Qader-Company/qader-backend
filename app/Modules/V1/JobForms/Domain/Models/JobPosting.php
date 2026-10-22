<?php

namespace App\Modules\V1\JobForms\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobPublishingStatusEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkModeEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkTypeEnum;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use SoftDeletes;

    protected $fillable = ['professional_field_id', 'job_title_id', 'title', 'slug', 'summary', 'about_role', 'work_type', 'work_mode', 'status', 'is_active', 'published_at', 'created_by_admin_id'];

    protected function casts(): array
    {
        return ['work_type' => JobWorkTypeEnum::class, 'work_mode' => JobWorkModeEnum::class, 'status' => JobPublishingStatusEnum::class, 'is_active' => 'boolean', 'published_at' => 'datetime'];
    }

    public function professionalField(): BelongsTo
    {
        return $this->belongsTo(ProfessionalField::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(JobPostingSection::class)->orderBy('display_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(JobFormVersion::class);
    }

    public function publishedVersions(): HasMany
    {
        return $this->versions()->where('status', 'published');
    }

    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(JobFormSubmission::class, JobFormVersion::class, 'job_posting_id', 'form_version_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
