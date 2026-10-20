<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSlot extends Model
{
    protected $fillable = [
        'interview_id', 'mode', 'starts_at', 'ends_at', 'location',
        'latitude', 'longitude', 'status',
    ];

    protected function casts(): array
    {
        return [
            'mode' => InterviewModeEnum::class,
            'status' => InterviewSlotStatusEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }
}
