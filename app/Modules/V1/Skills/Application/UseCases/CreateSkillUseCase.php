<?php

namespace App\Modules\V1\Skills\Application\UseCases;

use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Skills\Domain\Repositories\SkillRepositoryInterface;

class CreateSkillUseCase
{
    public function __construct(
        private readonly SkillRepositoryInterface $skills,
    ) {}

    public function execute(array $attributes): Skill
    {
        return $this->skills->create($attributes);
    }
}
