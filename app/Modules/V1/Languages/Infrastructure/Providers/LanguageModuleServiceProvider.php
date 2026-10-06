<?php

namespace App\Modules\V1\Languages\Infrastructure\Providers;

use App\Modules\V1\Languages\Domain\Repositories\LanguageRepositoryInterface;
use App\Modules\V1\Languages\Infrastructure\Persistence\EloquentLanguageRepository;
use Illuminate\Support\ServiceProvider;

class LanguageModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LanguageRepositoryInterface::class, EloquentLanguageRepository::class);
    }
}
