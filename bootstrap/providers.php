<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitServiceProvider;
use Ejarnutowski\LaravelApiKey\Providers\ApiKeyServiceProvider;

return [
    AppServiceProvider::class,
    RateLimitServiceProvider::class,
    ApiKeyServiceProvider::class,

];
