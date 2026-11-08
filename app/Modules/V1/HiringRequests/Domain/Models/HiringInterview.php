<?php

namespace App\Modules\V1\HiringRequests\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringInterview extends Model
{
    protected $fillable = [
        'request_item_id', 'status', 'starts_at', 'ends_at',
        'proposed_by_admin_id', 'proposed_at',
        'client_response', 'client_response_reason', 'client_responded_at',
        'talent_response', 'talent_response_reason', 'talent_responded_at',
        'confirmed_by_admin_id', 'confirmed_at',
        'google_calendar_event_id', 'meeting_url', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => HiringInterviewStatusEnum::class,
            'client_response' => HiringInterviewResponseEnum::class,
            'talent_response' => HiringInterviewResponseEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'proposed_at' => 'datetime',
            'client_responded_at' => 'datetime',
            'talent_responded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HiringRequestItem::class, 'request_item_id');
    }

    public function proposedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'proposed_by_admin_id');
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'confirmed_by_admin_id');
    }
}
