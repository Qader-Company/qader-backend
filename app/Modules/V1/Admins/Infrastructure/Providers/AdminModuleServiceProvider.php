<?php

namespace App\Modules\V1\Admins\Infrastructure\Providers;

use App\Modules\V1\Admins\Application\Contracts\AdminAvatarStorageInterface;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use App\Modules\V1\Admins\Infrastructure\Persistence\Repositories\EloquentAdminRepository;
use App\Modules\V1\Admins\Infrastructure\Storage\PublicAdminAvatarStorage;
use Illuminate\Support\ServiceProvider;

class AdminModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminRepositoryInterface::class,
            EloquentAdminRepository::class
        );
        $this->app->bind(
            AdminAvatarStorageInterface::class,
            PublicAdminAvatarStorage::class
        );
    }
}
