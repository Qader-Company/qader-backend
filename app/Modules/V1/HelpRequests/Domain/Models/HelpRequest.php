<?php

namespace App\Modules\V1\HelpRequests\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestClosureOutcomeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestContactMethodEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestTypeEnum;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpRequest extends Model
{
    public const REFERENCE_PREFIX = 'REQ-';

    public const REFERENCE_OFFSET = 1000;

    protected $fillable = [
        'reference', 'workspace_id', 'created_by_member_id', 'help_type', 'description',
        'contact_method', 'contact_email', 'contact_phone_country_code', 'contact_phone_number',
        'status', 'assigned_admin_id', 'started_at', 'closed_at', 'closed_by_admin_id',
        'closure_outcome', 'closure_details', 'resulting_hiring_request_id',
    ];

    protected static function booted(): void
    {
        static::created(function (self $request): void {
            if (blank($request->reference)) {
                $request->forceFill(['reference' => self::REFERENCE_PREFIX.(self::REFERENCE_OFFSET + $request->id)])->saveQuietly();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'help_type' => HelpRequestTypeEnum::class,
            'contact_method' => HelpRequestContactMethodEnum::class,
            'status' => HelpRequestStatusEnum::class,
            'closure_outcome' => HelpRequestClosureOutcomeEnum::class,
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMember::class, 'created_by_member_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function closedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'closed_by_admin_id');
    }

    public function resultingHiringRequest(): BelongsTo
    {
        return $this->belongsTo(HiringRequest::class, 'resulting_hiring_request_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(HelpRequestNote::class)->latest('created_at')->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(HelpRequestEvent::class)->latest('created_at')->latest('id');
    }
}
