<?php

namespace App\Modules\V1\Industries\Application\UseCases;

use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\Industries\Domain\Repositories\IndustryRepositoryInterface;

class UpdateIndustryUseCase
{
    public function __construct(private readonly IndustryRepositoryInterface $industries) {}

    public function execute(Industry $industry, array $attributes): Industry
    {
        return $this->industries->update($industry, $attributes);
    }
}
