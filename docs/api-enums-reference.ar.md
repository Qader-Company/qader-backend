# مرجع قيم الـAPI Enums

آخر مراجعة: 21 أكتوبر 2026.

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

```text
client.workspace.view
client.workspace.update
client.dashboard.view
client.members.view
client.members.create
client.members.manage
client.roles.view
client.roles.manage
client.marketplace.view
client.talent-selections.view
client.talent-selections.manage
```

صلاحيات العميل مرتبطة بالـWorkspace، وتظهر ضمن:

```text
GET /api/v1/client/access-control/workspaces/{workspace}/permissions
```

قيم ترتيب Talent Marketplace:

```text
highest_match
newest
experience_high_to_low
salary_low_to_high
salary_high_to_low
```

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
admin.talent-applications.view
admin.talent-applications.assign-reviewer
admin.talent-applications.review
admin.talent-applications.decide
admin.talent-review-faqs.view
admin.talent-review-faqs.manage
admin.interviews.view
admin.interviews.manage
admin.interviews.evaluate
admin.interviews.decide
admin.talents.view
admin.talents.manage-blacklist
admin.talents.manage-reapply
```

تُستخدم قيم Admin permissions عند إنشاء وتعديل الأدوار، وتظهر أيضًا في endpoint عرض الصلاحيات:

```text
GET  /api/v1/admin/access-control/permissions
POST /api/v1/admin/access-control/roles
PATCH /api/v1/admin/access-control/roles/{role}
```

## تقييم طلبات المواهب

قيم الاختيارات الخاصة بكل جزء متاحة من endpoints منفصلة حسب مكان استخدامها:

```text
GET /api/v1/admin/talent-applications/statuses
GET /api/v1/admin/talent-applications/scopes
GET /api/v1/admin/talent-applications/sort-options
GET /api/v1/admin/talent-applications/evaluation-tip-priorities
GET /api/v1/admin/talent-applications/reapply-policies
GET /api/v1/admin/talent-applications/interview-types
GET /api/v1/admin/talent-applications/interview-modes
GET /api/v1/admin/talent-applications/interview-statuses
GET /api/v1/admin/talent-applications/interview-slot-statuses
GET /api/v1/admin/talent-review-faqs/screen-states
```

كل endpoint يرجع `value` و`label` مترجمًا حسب `Accept-Language`. عند إضافة enum جديدة تستخدم كاختيار في واجهة الإدارة، تضاف تحت endpoint الموديول الذي يستخدمها بدل endpoint عامة موحدة.

### حالات الطلب

المصدر: `TalentApplicationStatusEnum`.

```text
submitted
under_review
interview_required
rejected
accepted
```

`accepted` محجوز للقرار النهائي في سبرنت لاحق.

### نطاق قائمة الطلبات

المصدر: `TalentApplicationListScopeEnum`.

```text
all
my
```

### ترتيب قائمة الطلبات

المصدر: `TalentApplicationSortEnum`.

```text
newest
oldest
```

### سياسة إعادة التقديم

المصدر: `ReapplyPolicyEnum`.

```text
allowed_after_period
blacklisted
```

### أولوية نصيحة التقييم

المصدر: `EvaluationTipPriorityEnum`.

```text
low
medium
high
```

### نوع المقابلة

المصدر: `InterviewTypeEnum`.

```text
hr
technical
general
final
```

### طريقة المقابلة

المصدر: `InterviewModeEnum`.

```text
online
offline
```

### حالة المقابلة

المصدر: `InterviewStatusEnum`.

```text
awaiting_slot_selection
awaiting_new_slots
confirmed
in_progress
completed
cancelled
```

### حالة شاشة طلب الموهبة

المصدر: `TalentApplicationScreenStateEnum`.

```text
under_review
rejected
interview_slot_required
awaiting_new_interview_slots
interview_confirmed
interview_in_progress
interview_completed
interview_cancelled
accepted
blacklisted
```

تُرجع في `screen_state` ويستخدمها الفرونت لاختيار شاشة الحالة، كما يستخدمها الأدمن لتحديد شاشات ظهور كل FAQ.

### حالة الموعد المقترح

المصدر: `InterviewSlotStatusEnum`.

```text
available
selected
expired
cancelled
rejected_by_talent
```

## S03 — Job Forms

القيم متاحة للأدمن من endpoints منفصلة تحت `/api/v1/admin/job-forms`.

### حالة نشر النموذج

```text
draft
published
```

### حالة التقديم الخارجي

```text
received
hiring_started
```

### نوع العمل

```text
full_time
part_time
contract
freelance
internship
```

### نمط العمل

```text
onsite
remote
hybrid
```

### نطاق الخبرة

```text
less_than_1
1_to_3
3_to_5
5_to_10
more_than_10
```

### مدة الإشعار بالأيام

```text
0
7
14
30
60
90
```

## ملفات المصدر

- Auth enums: `app/Modules/V1/Authentication/Domain/ValueObjects/`
- Portal types: `app/Modules/V1/Users/Domain/ValueObjects/PortalTypeEnum.php`
- Talent enums: `app/Modules/V1/Talents/Domain/ValueObjects/`
- Form field keys: `app/Modules/V1/ProfessionalFields/Domain/ValueObjects/ProfessionalFormFieldEnum.php`
- Permission enums: `app/Modules/V1/AccessControl/Domain/ValueObjects/`
- Talent application enums: `app/Modules/V1/TalentApplications/Domain/ValueObjects/`
- Job form enums: `app/Modules/V1/JobForms/Domain/ValueObjects/`
