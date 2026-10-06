<?php

namespace App\Modules\V1\Tools\Infrastructure\Providers;

use App\Modules\V1\Tools\Domain\Repositories\ToolRepositoryInterface;
use App\Modules\V1\Tools\Infrastructure\Persistence\EloquentToolRepository;
use Illuminate\Support\ServiceProvider;

class ToolModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ToolRepositoryInterface::class, EloquentToolRepository::class);
    }
}
