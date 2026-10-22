<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobPublishingStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
