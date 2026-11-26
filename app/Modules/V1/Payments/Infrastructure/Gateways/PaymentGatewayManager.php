<?php

namespace App\Modules\V1\Payments\Infrastructure\Gateways;

use App\Modules\V1\Payments\Application\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PaymentGatewayManager
{
    public function __construct(private readonly Container $container) {}

    public function default(): PaymentGatewayInterface
    {
        $provider = config('payments.default');

        if (! is_string($provider) || $provider === '') {
            throw new ServiceUnavailableHttpException(null, __('payments.gateway_not_configured'));
        }

        return $this->for($provider);
    }

    public function for(string $provider): PaymentGatewayInterface
    {
        $driver = config("payments.gateways.{$provider}.driver");

        if (! is_string($driver) || ! is_a($driver, PaymentGatewayInterface::class, true)) {
            throw new ServiceUnavailableHttpException(null, __('payments.gateway_not_supported'));
        }

        if ($provider === 'fake' && ! app()->environment(['local', 'testing'])) {
            throw new ServiceUnavailableHttpException(null, __('payments.gateway_not_configured'));
        }

        /** @var PaymentGatewayInterface $gateway */
        $gateway = $this->container->make($driver);

        return $gateway;
    }
}
