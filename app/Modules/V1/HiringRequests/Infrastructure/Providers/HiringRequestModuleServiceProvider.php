<?php

namespace App\Modules\V1\HiringRequests\Infrastructure\Providers;

use App\Modules\V1\HiringRequests\Application\Contracts\HiringInterviewCalendarGatewayInterface;
use App\Modules\V1\HiringRequests\Infrastructure\Calendar\GoogleHiringInterviewCalendarGateway;
use Illuminate\Support\ServiceProvider;

class HiringRequestModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            HiringInterviewCalendarGatewayInterface::class,
            GoogleHiringInterviewCalendarGateway::class,
        );
    }
}
