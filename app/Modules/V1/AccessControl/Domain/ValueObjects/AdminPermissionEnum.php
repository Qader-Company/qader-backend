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

    case INDUSTRIES_VIEW = 'admin.industries.view';
    case INDUSTRIES_MANAGE = 'admin.industries.manage';

    case TALENT_APPLICATIONS_VIEW = 'admin.talent-applications.view';
    case TALENT_APPLICATIONS_ASSIGN_REVIEWER = 'admin.talent-applications.assign-reviewer';
    case TALENT_APPLICATIONS_REVIEW = 'admin.talent-applications.review';
    case TALENT_APPLICATIONS_DECIDE = 'admin.talent-applications.decide';

    case INTERVIEWS_VIEW = 'admin.interviews.view';
    case INTERVIEWS_MANAGE = 'admin.interviews.manage';
    case INTERVIEWS_EVALUATE = 'admin.interviews.evaluate';
    case INTERVIEWS_DECIDE = 'admin.interviews.decide';

    case TALENTS_VIEW = 'admin.talents.view';
    case TALENTS_MANAGE_BLACKLIST = 'admin.talents.manage-blacklist';
    case TALENTS_MANAGE_REAPPLY = 'admin.talents.manage-reapply';

    case TALENT_REVIEW_FAQS_VIEW = 'admin.talent-review-faqs.view';
    case TALENT_REVIEW_FAQS_MANAGE = 'admin.talent-review-faqs.manage';

    case JOB_FORMS_VIEW = 'admin.job-forms.view';
    case JOB_FORMS_MANAGE = 'admin.job-forms.manage';
    case JOB_FORM_SUBMISSIONS_VIEW = 'admin.job-form-submissions.view';
    case JOB_FORM_SUBMISSIONS_DELETE = 'admin.job-form-submissions.delete';
    case JOB_FORM_SUBMISSIONS_START_HIRING = 'admin.job-form-submissions.start-hiring';

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::ADMIN;
    }
}
