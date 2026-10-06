<?php

namespace App\Modules\V1\JobTitles\Domain\Repositories;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface JobTitleRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): JobTitle;

    public function findOrFail(int $id): JobTitle;

    public function update(JobTitle $jobTitle, array $attributes): JobTitle;
}
