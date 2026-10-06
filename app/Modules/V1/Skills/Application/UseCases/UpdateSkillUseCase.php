<?php

namespace App\Modules\V1\Skills\Application\UseCases;

use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Skills\Domain\Repositories\SkillRepositoryInterface;

class UpdateSkillUseCase
{
    public function __construct(
        private readonly SkillRepositoryInterface $skills,
    ) {}

    public function execute(int $id, array $attributes): Skill
    {
        $skill = $this->skills->findOrFail($id);

        return $this->skills->update($skill, $attributes);
    }
}
