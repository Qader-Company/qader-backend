<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interview extends Model
{
    public const MAX_SLOT_REJECTIONS = 2;

    protected $fillable = [
        'application_id', 'round_number', 'type', 'status', 'created_by_admin_id',
        'google_calendar_event_id', 'meeting_url', 'rejected_slots_reason',
        'slot_rejection_count', 'talent_reschedule_used_at', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterviewTypeEnum::class,
            'status' => InterviewStatusEnum::class,
            'slot_rejection_count' => 'integer',
            'round_number' => 'integer',
            'talent_reschedule_used_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TalentApplication::class, 'application_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'interview_admin');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(InterviewSlot::class)->orderBy('starts_at');
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(InterviewEvaluation::class);
    }

    public function selectedSlot(): HasOne
    {
        return $this->hasOne(InterviewSlot::class)
            ->where('status', InterviewSlotStatusEnum::SELECTED->value);
    }
}
