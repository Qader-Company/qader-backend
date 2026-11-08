<?php

namespace App\Modules\V1\JobForms\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobSubmissionStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobFormSubmission extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = ['form_version_id', 'applicant_name', 'email', 'phone_country_code', 'phone_number', 'status', 'idempotency_key', 'consent_at', 'submitted_at', 'converted_talent_id', 'converted_by_admin_id', 'converted_at'];

    protected function casts(): array
    {
        return ['status' => JobSubmissionStatusEnum::class, 'consent_at' => 'datetime', 'submitted_at' => 'datetime', 'converted_at' => 'datetime'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(JobFormVersion::class, 'form_version_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(JobFormAnswer::class, 'submission_id');
    }

    public function convertedTalent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'converted_talent_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'converted_by_admin_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cv')->singleFile()->useDisk('local')->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }
}
