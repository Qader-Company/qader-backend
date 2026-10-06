<?php

namespace App\Modules\V1\Countries\Application\UseCases;

use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Countries\Domain\Repositories\CountryRepositoryInterface;

class UpdateCountryUseCase
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function execute(Country $country, array $attributes): Country
    {
        return $this->countries->update($country, $attributes);
    }
}
