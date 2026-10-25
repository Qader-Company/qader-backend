<?php

namespace App\Modules\V1\Industries\Domain\Repositories;

use App\Modules\V1\Industries\Domain\Models\Industry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface IndustryRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(): Collection;

    public function create(array $attributes): Industry;

    public function update(Industry $industry, array $attributes): Industry;
}
