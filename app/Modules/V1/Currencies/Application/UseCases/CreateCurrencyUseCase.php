<?php

namespace App\Modules\V1\Currencies\Application\UseCases;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\Currencies\Domain\Repositories\CurrencyRepositoryInterface;

class CreateCurrencyUseCase
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
    ) {}

    public function execute(array $attributes): Currency
    {
        return $this->currencies->create($attributes);
    }
}
