<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

use App\Modules\V1\AccessControl\Domain\ValueObjects\Concerns\InteractsWithPermissions;
use App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts\PermissionDefinition;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

enum AdminPermissionEnum: string implements PermissionDefinition
{
    use InteractsWithPermissions;

    case ACCESS_CONTROL_VIEW = 'admin.access-control.view';
    case ACCESS_CONTROL_MANAGE_ROLES = 'admin.access-control.manage-roles';

    case ADMINS_VIEW = 'admin.admins.view';
    case ADMINS_MANAGE = 'admin.admins.manage';

    case COUNTRIES_VIEW = 'admin.countries.view';
    case COUNTRIES_MANAGE = 'admin.countries.manage';

    case CITIES_VIEW = 'admin.cities.view';
    case CITIES_MANAGE = 'admin.cities.manage';

    case LANGUAGES_VIEW = 'admin.languages.view';
    case LANGUAGES_MANAGE = 'admin.languages.manage';

    case PROFESSIONAL_FIELDS_VIEW = 'admin.professional-fields.view';
    case PROFESSIONAL_FIELDS_MANAGE = 'admin.professional-fields.manage';

    case JOB_TITLES_VIEW = 'admin.job-titles.view';
    case JOB_TITLES_MANAGE = 'admin.job-titles.manage';

    case SKILLS_VIEW = 'admin.skills.view';
    case SKILLS_MANAGE = 'admin.skills.manage';

    case TOOLS_VIEW = 'admin.tools.view';
    case TOOLS_MANAGE = 'admin.tools.manage';

    case CURRENCIES_VIEW = 'admin.currencies.view';
    case CURRENCIES_MANAGE = 'admin.currencies.manage';

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::ADMIN;
    }
}
