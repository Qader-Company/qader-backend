<?php

namespace App\Modules\V1\PlatformSettings\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSettingEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_admin_id', 'group', 'old_values', 'new_values', 'created_at'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }
}
