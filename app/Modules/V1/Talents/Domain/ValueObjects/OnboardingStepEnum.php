<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum OnboardingStepEnum: string
{
    case PERSONAL_INFORMATION = 'personal-information';
    case SKILLS_AND_EXPERIENCES = 'skills-and-experiences';
    case WORK_PREFERENCES = 'work-preferences';

    public static function values(): array
    {
        return array_map(
            static fn (self $step): string => $step->value,
            self::cases(),
        );
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function previousSteps(): array
    {
        return match ($this) {
            self::PERSONAL_INFORMATION => [],
            self::SKILLS_AND_EXPERIENCES => [
                self::PERSONAL_INFORMATION,
            ],
            self::WORK_PREFERENCES => [
                self::PERSONAL_INFORMATION,
                self::SKILLS_AND_EXPERIENCES,
            ],
        };
    }
}
