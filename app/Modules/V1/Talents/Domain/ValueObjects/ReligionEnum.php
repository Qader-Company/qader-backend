<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum ReligionEnum: string
{
    case ISLAM = 'islam';
    case CHRISTIANITY = 'christianity';
    case JUDAISM = 'judaism';
    case OTHER = 'other';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';
}
