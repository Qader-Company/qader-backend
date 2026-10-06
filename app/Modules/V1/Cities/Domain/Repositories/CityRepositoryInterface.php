<?php

namespace App\Modules\V1\Cities\Domain\Repositories;

use App\Modules\V1\Cities\Domain\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CityRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): City;

    public function update(City $city, array $attributes): City;
}
