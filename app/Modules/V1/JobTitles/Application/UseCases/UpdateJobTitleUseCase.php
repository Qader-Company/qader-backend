<?php

namespace App\Modules\V1\JobTitles\Application\UseCases;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\JobTitles\Domain\Repositories\JobTitleRepositoryInterface;

class UpdateJobTitleUseCase
{
    public function __construct(
        private readonly JobTitleRepositoryInterface $jobTitles,
    ) {}

    public function execute(int $id, array $attributes): JobTitle
    {
        $jobTitle = $this->jobTitles->findOrFail($id);

        return $this->jobTitles->update($jobTitle, $attributes);
    }
}
