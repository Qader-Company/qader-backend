# مرجع قيم الـAPI Enums

آخر مراجعة: 19 أكتوبر 2026.

هذا الملف هو المرجع المجمع للقيم الثابتة المعرفة كـPHP enums والمستخدمة في عقود الـAPI. لا يشمل الـMaster Data المحفوظة في جداول، مثل الدول والمدن والعملات والمجالات المهنية والمسميات والمهارات والأدوات.

عند إضافة enum جديد، أو تعديل قيمة موجودة، يجب تحديث هذا الملف في نفس التغيير.

## Enums مشتركة بين الأدوار

### Portal types

المصدر: `PortalTypeEnum`.

```text
talent
client
admin
```

- التسجيل متاح حاليًا لـ`talent` و`client`.
- تسجيل الدخول وتجديد الـtoken متاحان لـ`talent` و`client` و`admin`.

### Social providers

المصدر: `SocialProviderEnum`.

```text
google
```

تُستخدم القيمة في:

```text
POST /api/v1/auth/{type}/social/{provider}/login
```

### OTP purposes

المصدر: `OtpPurposeEnum`.

```text
email-verification
password-reset
```

تُستخدم القيمة في:

```text
POST /api/v1/auth/{type}/{purpose}/send-otp
```

### Token types

المصدر: `TokenTypeEnum`.

```text
access
refresh
verification
reset_password
```

هذه القيم تحدد قدرة الـSanctum token واستخدامه:

- `access`: الوصول العادي للـPortal.
- `refresh`: إصدار access token جديد.
- `verification`: تأكيد البريد الإلكتروني.
- `reset_password`: إعادة تعيين كلمة المرور بعد التحقق من OTP.

## Talent enums

### Gender

المصدر: `GenderEnum`.

```text
male
female
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/genders
```

### Religion

المصدر: `ReligionEnum`.

```text
islam
christianity
judaism
other
prefer_not_to_say
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/religions
```

### Language proficiency

المصدر: `LanguageProficiencyEnum`.

```text
a1
a2
b1
b2
c1
c2
native
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/language-proficiencies
```

### Seniority level

المصدر: `SeniorityLevelEnum`.

```text
intern
junior
mid_level
senior
lead
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/seniority-levels
```

### Work type

المصدر: `WorkTypeEnum`.

```text
full_time
part_time
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/work-types
```

### Work mode

المصدر: `WorkModeEnum`.

```text
remote
onsite
hybrid
```

Endpoint الخيارات:

```text
GET /api/v1/talent/master-data/work-modes
```

## Talent onboarding

### Onboarding steps

المصدر: `OnboardingStepEnum`.

```text
personal-information
skills-and-experiences
work-preferences
```

تُستخدم القيمة في:

```text
POST /api/v1/talent/onboarding/{step}
```

ترتيب التنفيذ:

1. `personal-information`
2. `skills-and-experiences`
3. `work-preferences`

### Onboarding step statuses

المصدر: `OnboardingStepStatusEnum`.

```text
not_started
in_progress
completed
```

هذه القيم تظهر في استجابة تقدم الـonboarding.

## Professional form field keys

المصدر: `ProfessionalFormFieldEnum`.

القيم التالية هي المفاتيح التي يستطيع الـBackend إرجاعها للفرونت حسب المجال المهني. الخطوة والترتيب وشكل العرض يحددها الفرونت، بينما قواعد الحفظ والتحقق الأساسية تبقى في الـBackend.

### Personal information

```text
professional_field_id
full_name
email
phone_number
whatsapp_number
date_of_birth
gender
religion
nationality_country_id
residence_country_id
residence_city_id
languages
cv
```

### Skills and experiences

```text
professional_summary
job_title_id
seniority_level
years_of_experience
skill_ids
tool_ids
linkedin_url
github_url
behance_url
portfolio_url
experiences
projects
```

### Work preferences

```text
work_types
work_modes
willing_to_relocate
notice_period_days
current_salary
expected_salary
```

### Required by default

```text
professional_field_id
full_name
email
phone_number
whatsapp_number
gender
job_title_id
seniority_level
years_of_experience
skill_ids
linkedin_url
work_types
work_modes
notice_period_days
current_salary
expected_salary
cv
```

الحقول المطلوبة دائمًا داخل منطق الـBackend:

```text
professional_field_id
full_name
email
```

## Permission enums

### Talent permissions

المصدر: `TalentPermissionEnum`.

```text
talent.platform.access
```

### Client permissions

المصدر: `ClientPermissionEnum`.

لا توجد قيم معرفة حاليًا.

### Admin permissions

المصدر: `AdminPermissionEnum`.

```text
admin.access-control.view
admin.access-control.manage-roles
admin.admins.view
admin.admins.manage
admin.countries.view
admin.countries.manage
admin.cities.view
admin.cities.manage
admin.languages.view
admin.languages.manage
admin.professional-fields.view
admin.professional-fields.manage
admin.job-titles.view
admin.job-titles.manage
admin.skills.view
admin.skills.manage
admin.tools.view
admin.tools.manage
admin.currencies.view
admin.currencies.manage
```

تُستخدم قيم Admin permissions عند إنشاء وتعديل الأدوار، وتظهر أيضًا في endpoint عرض الصلاحيات:

```text
GET  /api/v1/admin/access-control/permissions
POST /api/v1/admin/access-control/roles
PATCH /api/v1/admin/access-control/roles/{role}
```

## ملفات المصدر

- Auth enums: `app/Modules/V1/Authentication/Domain/ValueObjects/`
- Portal types: `app/Modules/V1/Users/Domain/ValueObjects/PortalTypeEnum.php`
- Talent enums: `app/Modules/V1/Talents/Domain/ValueObjects/`
- Form field keys: `app/Modules/V1/ProfessionalFields/Domain/ValueObjects/ProfessionalFormFieldEnum.php`
- Permission enums: `app/Modules/V1/AccessControl/Domain/ValueObjects/`
