<?php

namespace App\Modules\V1\TalentApplications\Infrastructure\Providers;

use App\Modules\V1\TalentApplications\Application\Contracts\InterviewCalendarGatewayInterface;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;
use App\Modules\V1\TalentApplications\Infrastructure\Calendar\GoogleInterviewCalendarGateway;
use App\Modules\V1\TalentApplications\Infrastructure\Persistence\Repositories\EloquentTalentApplicationRepository;
use Illuminate\Support\ServiceProvider;

class TalentApplicationModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TalentApplicationRepositoryInterface::class,
            EloquentTalentApplicationRepository::class,
        );
        $this->app->bind(
            InterviewCalendarGatewayInterface::class,
            GoogleInterviewCalendarGateway::class,
        );
    }
}
