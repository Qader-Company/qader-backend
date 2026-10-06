<?php

namespace App\Modules\V1\Countries\Domain\Repositories;

use App\Modules\V1\Countries\Domain\Models\Country;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CountryRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(): Collection;

    public function create(array $attributes): Country;

    public function update(Country $country, array $attributes): Country;
}
