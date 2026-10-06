<?php

namespace App\Modules\V1\Talents\Infrastructure\Providers;

use App\Modules\V1\ProfessionalFields\Domain\Events\ProfessionalFieldFormConfigurationUpdated;
use App\Modules\V1\Talents\Application\Listeners\RefreshTalentsAfterProfessionalFieldFormUpdated;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Talents\Infrastructure\Persistence\Repositories\EloquentTalentRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TalentModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TalentRepositoryInterface::class, EloquentTalentRepository::class);
    }

    public function boot(): void
    {
        Event::listen(
            ProfessionalFieldFormConfigurationUpdated::class,
            RefreshTalentsAfterProfessionalFieldFormUpdated::class,
        );
    }
}
