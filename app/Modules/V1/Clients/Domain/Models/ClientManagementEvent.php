<?php

namespace App\Modules\V1\Clients\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientManagementActionEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientManagementEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['client_id', 'workspace_id', 'workspace_member_id', 'actor_admin_id', 'action', 'reason', 'created_at'];

    protected function casts(): array
    {
        return ['action' => ClientManagementActionEnum::class, 'created_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class)->withTrashed();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }
}
