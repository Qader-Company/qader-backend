<?php

namespace App\Modules\V1\Skills\Infrastructure\Providers;

use App\Modules\V1\Skills\Domain\Repositories\SkillRepositoryInterface;
use App\Modules\V1\Skills\Infrastructure\Persistence\EloquentSkillRepository;
use Illuminate\Support\ServiceProvider;

class SkillModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SkillRepositoryInterface::class, EloquentSkillRepository::class);
    }
}
