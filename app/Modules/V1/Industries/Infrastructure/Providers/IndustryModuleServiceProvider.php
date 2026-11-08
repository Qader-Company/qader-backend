<?php

namespace App\Modules\V1\Industries\Infrastructure\Providers;

use App\Modules\V1\Industries\Domain\Repositories\IndustryRepositoryInterface;
use App\Modules\V1\Industries\Infrastructure\Persistence\EloquentIndustryRepository;
use Illuminate\Support\ServiceProvider;

class IndustryModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IndustryRepositoryInterface::class, EloquentIndustryRepository::class);
    }
}
