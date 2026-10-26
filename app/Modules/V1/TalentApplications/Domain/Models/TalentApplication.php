<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TalentApplication extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'talent_id',
        'status',
        'submitted_at',
        'submission_snapshot',
        'profile_completion_at_submission',
        'snapshot_version',
        'snapshot_captured_at',
        'assigned_admin_id',
        'review_decided_by_admin_id',
        'review_decided_at',
        'reapply_at',
    ];

    protected function casts(): array
    {
        return [
            'talent_id' => 'integer',
            'assigned_admin_id' => 'integer',
            'review_decided_by_admin_id' => 'integer',
            'submitted_at' => 'datetime',
            'submission_snapshot' => 'array',
            'profile_completion_at_submission' => 'integer',
            'snapshot_version' => 'integer',
            'snapshot_captured_at' => 'datetime',
            'status' => TalentApplicationStatusEnum::class,
            'review_decided_at' => 'datetime',
            'reapply_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('submitted_cv')->singleFile();
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function reviewerAssignments(): HasMany
    {
        return $this->hasMany(TalentApplicationReviewerAssignment::class, 'application_id');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(TalentEvaluation::class, 'application_id');
    }

    public function interview(): HasOne
    {
        return $this->hasOne(Interview::class, 'application_id')->latestOfMany('round_number');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id')->orderBy('round_number');
    }

    public function blacklistEntry(): HasOne
    {
        return $this->hasOne(TalentBlacklistEntry::class, 'application_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TalentApplicationEvent::class, 'application_id');
    }
}
