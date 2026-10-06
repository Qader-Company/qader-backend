<?php

namespace App\Modules\V1\Languages\Domain\Repositories;

use App\Modules\V1\Languages\Domain\Models\Language;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LanguageRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function listActive(array $filters): Collection;

    public function create(array $attributes): Language;

    public function findOrFail(int $id): Language;

    public function update(Language $language, array $attributes): Language;
}
