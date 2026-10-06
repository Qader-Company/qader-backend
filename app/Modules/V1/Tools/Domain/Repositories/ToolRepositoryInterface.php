<?php

namespace App\Modules\V1\Tools\Domain\Repositories;

use App\Modules\V1\Tools\Domain\Models\Tool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ToolRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): Tool;

    public function findOrFail(int $id): Tool;

    public function update(Tool $tool, array $attributes): Tool;
}
