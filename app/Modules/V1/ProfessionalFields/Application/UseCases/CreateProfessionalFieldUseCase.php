<?php

namespace App\Modules\V1\ProfessionalFields\Application\UseCases;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateProfessionalFieldUseCase
{
    public function __construct(
        private readonly ProfessionalFieldRepositoryInterface $professionalFields,
        private readonly ProfessionalFormFieldRepositoryInterface $formFields,
    ) {}

    public function execute(array $attributes): ProfessionalField
    {
        return DB::transaction(function () use ($attributes): ProfessionalField {
            $fields = Arr::pull($attributes, 'form_fields');
            $professionalField = $this->professionalFields->create($attributes);

            if ($fields !== null) {
                $this->formFields->sync(
                    $professionalField->id,
                    $fields,
                );
            }

            return $professionalField;
        });
    }
}
