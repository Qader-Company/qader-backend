<?php

namespace App\Modules\V1\Cities\Application\UseCases;

use App\Modules\V1\Cities\Domain\Repositories\CityRepositoryInterface;

class ListCitiesUseCase
{
    public function __construct(private readonly CityRepositoryInterface $cities) {}

    public function execute(bool $forAdmin, array $filters = [])
    {
        if ($forAdmin) {
            return $this->cities->paginate($filters);
        }

        return $this->cities->listActive($filters);
    }
}
