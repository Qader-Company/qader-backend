<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Providers;

use App\Modules\V1\AccessControl\Domain\Repositories\PermissionRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use Illuminate\Support\ServiceProvider;

class AccessControlModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PermissionRepositoryInterface::class,
            EloquentPermissionRepository::class
        );
        $this->app->bind(
            RoleRepositoryInterface::class,
            EloquentRoleRepository::class
        );
    }
}
