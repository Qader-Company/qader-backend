<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'submission_snapshot' => 'array',
            'profile_completion_at_submission' => 'integer',
            'snapshot_version' => 'integer',
            'snapshot_captured_at' => 'datetime',
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
}
