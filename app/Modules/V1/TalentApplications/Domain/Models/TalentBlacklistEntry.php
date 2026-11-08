<?php

namespace App\Modules\V1\TalentApplications\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentBlacklistEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'talent_id', 'application_id', 'blacklist_reason',
        'blocked_by_admin_id', 'blocked_at', 'removed_at', 'removed_by_admin_id',
    ];

    protected function casts(): array
    {
        return ['blocked_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TalentApplication::class, 'application_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'blocked_by_admin_id');
    }
}
