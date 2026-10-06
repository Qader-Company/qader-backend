<?php

namespace App\Modules\V1\Cities\Infrastructure\Providers;

use App\Modules\V1\Cities\Domain\Repositories\CityRepositoryInterface;
use App\Modules\V1\Cities\Infrastructure\Persistence\EloquentCityRepository;
use Illuminate\Support\ServiceProvider;

class CityModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CityRepositoryInterface::class, EloquentCityRepository::class);
    }
}
