<?php

namespace App\Modules\V1\JobTitles\Infrastructure\Providers;

use App\Modules\V1\JobTitles\Domain\Repositories\JobTitleRepositoryInterface;
use App\Modules\V1\JobTitles\Infrastructure\Persistence\EloquentJobTitleRepository;
use Illuminate\Support\ServiceProvider;

class JobTitleModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JobTitleRepositoryInterface::class, EloquentJobTitleRepository::class);
    }
}
