<?php

namespace App\Modules\V1\Languages\Application\UseCases;

use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Languages\Domain\Repositories\LanguageRepositoryInterface;

class UpdateLanguageUseCase
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languages,
    ) {}

    public function execute(int $id, array $attributes): Language
    {
        $language = $this->languages->findOrFail($id);

        return $this->languages->update($language, $attributes);
    }
}
