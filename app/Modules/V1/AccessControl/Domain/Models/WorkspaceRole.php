<?php

namespace App\Modules\V1\AccessControl\Domain\Models;

use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkspaceRole extends Model
{
    protected $fillable = ['workspace_id', 'key', 'name_en', 'name_ar', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'workspace_role_permissions');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(WorkspaceMember::class, 'workspace_member_roles');
    }
}
