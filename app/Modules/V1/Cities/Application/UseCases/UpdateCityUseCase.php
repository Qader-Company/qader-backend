<?php

namespace App\Modules\V1\Cities\Application\UseCases;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Cities\Domain\Repositories\CityRepositoryInterface;

class UpdateCityUseCase
{
    public function __construct(private readonly CityRepositoryInterface $cities) {}

    public function execute(City $city, array $attributes): City
    {
        return $this->cities->update($city, $attributes);
    }
}
