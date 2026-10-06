<?php

namespace App\Modules\V1\Languages\Application\UseCases;

use App\Modules\V1\Languages\Domain\Repositories\LanguageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListLanguagesUseCase
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languages,
    ) {}

    public function execute(
        bool $forAdmin,
        array $filters = [],
    ): LengthAwarePaginator|Collection {
        if ($forAdmin) {
            return $this->languages->paginate($filters);
        }

        return $this->languages->listActive($filters);
    }
}
