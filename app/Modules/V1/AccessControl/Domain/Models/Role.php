<?php

namespace App\Modules\V1\AccessControl\Domain\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name', 'name_en', 'name_ar', 'guard_name', 'portal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
