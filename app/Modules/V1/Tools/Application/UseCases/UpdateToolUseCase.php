<?php

namespace App\Modules\V1\Tools\Application\UseCases;

use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Tools\Domain\Repositories\ToolRepositoryInterface;

class UpdateToolUseCase
{
    public function __construct(
        private readonly ToolRepositoryInterface $tools,
    ) {}

    public function execute(int $id, array $attributes): Tool
    {
        $tool = $this->tools->findOrFail($id);

        return $this->tools->update($tool, $attributes);
    }
}
