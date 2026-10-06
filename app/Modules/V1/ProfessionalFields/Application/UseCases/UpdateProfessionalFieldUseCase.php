<?php

namespace App\Modules\V1\ProfessionalFields\Application\UseCases;

use App\Modules\V1\ProfessionalFields\Domain\Events\ProfessionalFieldFormConfigurationUpdated;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateProfessionalFieldUseCase
{
    public function __construct(
        private readonly ProfessionalFieldRepositoryInterface $professionalFields,
        private readonly ProfessionalFormFieldRepositoryInterface $formFields,
    ) {}

    public function execute(int $id, array $attributes): ProfessionalField
    {
        $formFieldsWereUpdated = array_key_exists('form_fields', $attributes);

        $professionalField = DB::transaction(function () use ($id, $attributes): ProfessionalField {
            $fields = Arr::pull($attributes, 'form_fields');
            $professionalField = $this->professionalFields->findOrFail($id);
            $professionalField = $this->professionalFields->update(
                $professionalField,
                $attributes,
            );

            if ($fields !== null) {
                $this->formFields->sync(
                    $professionalField->id,
                    $fields,
                );
            }

            return $professionalField;
        });

        if ($formFieldsWereUpdated) {
            event(new ProfessionalFieldFormConfigurationUpdated(
                $professionalField->id,
            ));
        }

        return $professionalField;
    }
}
