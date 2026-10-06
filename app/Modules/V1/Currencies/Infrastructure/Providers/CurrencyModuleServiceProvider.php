<?php

namespace App\Modules\V1\Currencies\Infrastructure\Providers;

use App\Modules\V1\Currencies\Domain\Repositories\CurrencyRepositoryInterface;
use App\Modules\V1\Currencies\Infrastructure\Persistence\EloquentCurrencyRepository;
use Illuminate\Support\ServiceProvider;

class CurrencyModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CurrencyRepositoryInterface::class, EloquentCurrencyRepository::class);
    }
}
