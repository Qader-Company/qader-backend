<?php

namespace App\Modules\V1\Currencies\Domain\Repositories;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CurrencyRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): Currency;

    public function findOrFail(int $id): Currency;

    public function update(Currency $currency, array $attributes): Currency;
}
