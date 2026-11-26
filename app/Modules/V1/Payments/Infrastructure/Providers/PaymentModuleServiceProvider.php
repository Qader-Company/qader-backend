<?php

namespace App\Modules\V1\Payments\Infrastructure\Providers;

use App\Modules\V1\Payments\Infrastructure\Gateways\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class PaymentModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
    }
}
