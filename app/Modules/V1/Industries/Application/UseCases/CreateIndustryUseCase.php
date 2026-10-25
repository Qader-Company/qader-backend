<?php

namespace App\Modules\V1\Industries\Application\UseCases;

use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\Industries\Domain\Repositories\IndustryRepositoryInterface;

class CreateIndustryUseCase
{
    public function __construct(private readonly IndustryRepositoryInterface $industries) {}

    public function execute(array $attributes): Industry
    {
        return $this->industries->create($attributes);
    }
}
