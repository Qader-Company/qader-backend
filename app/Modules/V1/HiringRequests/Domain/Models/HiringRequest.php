<?php

namespace App\Modules\V1\HiringRequests\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HiringRequest extends Model
{
    protected $fillable = [
        'workspace_id', 'reference', 'created_by_member_id',
        'assigned_admin_id', 'status', 'submitted_at', 'started_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (blank($request->reference)) {
                $request->reference = self::uniqueReference('HR');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => HiringRequestStatusEnum::class,
            'submitted_at' => 'datetime',
            'started_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class)->withTrashed();
    }

    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMember::class, 'created_by_member_id')->withTrashed();
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HiringRequestItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(HiringRequestEvent::class)->latest('created_at');
    }

    private static function uniqueReference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->year.'-'.Str::upper(Str::random(8));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
