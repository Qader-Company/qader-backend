<?php

namespace App\Modules\V1\Workspaces\Domain\Models;

use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSocialPlatformEnum;
use Illuminate\Database\Eloquent\Model;

class WorkspaceSocialLink extends Model
{
    protected $fillable = ['platform', 'url'];

    protected function casts(): array
    {
        return ['platform' => WorkspaceSocialPlatformEnum::class];
    }
}
