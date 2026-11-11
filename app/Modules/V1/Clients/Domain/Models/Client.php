<?php

namespace App\Modules\V1\Clients\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'phone_country_code', 'phone_number'])]
class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_client_id');
    }

    public function managementEvents(): HasMany
    {
        return $this->hasMany(ClientManagementEvent::class)->latest('created_at')->latest('id');
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }
}
