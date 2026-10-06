<?php

namespace App\Modules\V1\TalentApplications\Application\Data;

readonly class ApplicationFormFieldPolicy
{
    public function __construct(
        public array $visibleFields,
        public array $requiredFields,
    ) {}

    public function toArray(): array
    {
        return [
            'visible_fields' => $this->visibleFields,
            'required_fields' => $this->requiredFields,
        ];
    }
}
