<?php

namespace App\Modules\V1\Countries\Application\UseCases;

use App\Modules\V1\Countries\Domain\Repositories\CountryRepositoryInterface;

class ListCountriesUseCase
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function execute(bool $forAdmin, array $filters = [])
    {
        return $forAdmin
            ? $this->countries->paginate($filters)
            : $this->countries->listActive();
    }
}
