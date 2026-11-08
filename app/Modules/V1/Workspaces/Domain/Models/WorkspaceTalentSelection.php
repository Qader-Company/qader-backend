<?php

namespace App\Modules\V1\Workspaces\Domain\Models;

use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceTalentSelection extends Model
{
    public $incrementing = false;

    protected $fillable = ['workspace_id', 'talent_id', 'added_by_member_id'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function addedByMember(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMember::class, 'added_by_member_id');
    }
}
