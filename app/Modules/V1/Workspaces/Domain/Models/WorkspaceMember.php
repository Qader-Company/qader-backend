<?php

namespace App\Modules\V1\Workspaces\Domain\Models;

use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceMember extends Model
{
    use SoftDeletes;

    protected $fillable = ['workspace_id', 'client_id', 'status', 'joined_at'];

    protected function casts(): array
    {
        return ['status' => WorkspaceMemberStatusEnum::class, 'joined_at' => 'datetime'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(WorkspaceRole::class, 'workspace_member_roles');
    }
}
