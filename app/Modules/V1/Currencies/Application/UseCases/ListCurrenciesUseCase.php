<?php

namespace App\Modules\V1\Currencies\Application\UseCases;

use App\Modules\V1\Currencies\Domain\Repositories\CurrencyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListCurrenciesUseCase
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->currencies->paginate($filters);
        }

        return $this->currencies->listActive($filters);
    }
}
