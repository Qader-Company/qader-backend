<?php

namespace App\Modules\V1\Skills\Domain\Repositories;

use App\Modules\V1\Skills\Domain\Models\Skill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SkillRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): Skill;

    public function findOrFail(int $id): Skill;

    public function update(Skill $skill, array $attributes): Skill;
}
