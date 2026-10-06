<?php

namespace App\Modules\V1\Tools\Application\UseCases;

use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Tools\Domain\Repositories\ToolRepositoryInterface;

class CreateToolUseCase
{
    public function __construct(
        private readonly ToolRepositoryInterface $tools,
    ) {}

    public function execute(array $attributes): Tool
    {
        return $this->tools->create($attributes);
    }
}
