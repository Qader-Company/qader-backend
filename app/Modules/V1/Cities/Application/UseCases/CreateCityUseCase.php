<?php

namespace App\Modules\V1\Cities\Application\UseCases;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Cities\Domain\Repositories\CityRepositoryInterface;

class CreateCityUseCase
{
    public function __construct(private readonly CityRepositoryInterface $cities) {}

    public function execute(array $attributes): City
    {
        return $this->cities->create($attributes);
    }
}
