<?php

namespace App\Modules\V1\Countries\Infrastructure\Providers;

use App\Modules\V1\Countries\Domain\Repositories\CountryRepositoryInterface;
use App\Modules\V1\Countries\Infrastructure\Persistence\EloquentCountryRepository;
use Illuminate\Support\ServiceProvider;

class CountryModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CountryRepositoryInterface::class, EloquentCountryRepository::class);
    }
}
