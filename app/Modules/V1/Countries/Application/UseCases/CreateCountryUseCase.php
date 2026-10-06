<?php

namespace App\Modules\V1\Countries\Application\UseCases;

use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Countries\Domain\Repositories\CountryRepositoryInterface;

class CreateCountryUseCase
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function execute(array $attributes): Country
    {
        return $this->countries->create($attributes);
    }
}
