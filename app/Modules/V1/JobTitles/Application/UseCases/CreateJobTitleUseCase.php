<?php

namespace App\Modules\V1\JobTitles\Application\UseCases;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\JobTitles\Domain\Repositories\JobTitleRepositoryInterface;

class CreateJobTitleUseCase
{
    public function __construct(
        private readonly JobTitleRepositoryInterface $jobTitles,
    ) {}

    public function execute(array $attributes): JobTitle
    {
        return $this->jobTitles->create($attributes);
    }
}
