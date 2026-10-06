<?php

namespace App\Modules\V1\Languages\Application\UseCases;

use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Languages\Domain\Repositories\LanguageRepositoryInterface;

class CreateLanguageUseCase
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languages,
    ) {}

    public function execute(array $attributes): Language
    {
        return $this->languages->create($attributes);
    }
}
