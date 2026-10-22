<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobFormVersionStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case RETIRED = 'retired';
}
