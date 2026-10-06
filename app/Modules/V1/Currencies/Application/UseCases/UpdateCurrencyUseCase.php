<?php

namespace App\Modules\V1\Currencies\Application\UseCases;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\Currencies\Domain\Repositories\CurrencyRepositoryInterface;

class UpdateCurrencyUseCase
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
    ) {}

    public function execute(int $id, array $attributes): Currency
    {
        $currency = $this->currencies->findOrFail($id);

        return $this->currencies->update($currency, $attributes);
    }
}
