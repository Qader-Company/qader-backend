# S01 — توثيق التنفيذ وتسليم الـAPI

الحالة: **منفذ**  
تاريخ التسليم: 18 أكتوبر 2026

## ما تم تنفيذه

- تحديث migrations الإنشاء الموجودة لجداول S01 مباشرة: تبسيط كتالوج الحقول المهنية، وإضافة وصف المجال وsnapshot التقديم، بدون migration تجميعية جديدة.
- `professional_form_fields` يخزن `id`, `key`, timestamps فقط.
- `professional_field_form_field` يربط المجال بالـinput keys فقط.
- قواعد الخطوات والحقول المطلوبة بقيت داخل كود الـBackend لضمان صحة الحفظ والـSubmit.
- إضافة اختيار المجال كعملية مستقلة، ومسح Job Title وSkills وTools عند تغييره حتى لا تبقى قيم من مجال قديم.
- نقل CV إلى `personal-information` ونقل Professional Summary إلى `skills-and-experiences`.
- منع تعديل البريد من profile؛ البريد يظهر في الاستجابة ويحتاج flow تحقق مستقل لتغييره.
- تثبيت الفرق بين `PATCH /profile` لحفظ Draft جزئي و`POST /onboarding/{step}` لتنفيذ Next أو Submit بقواعد required.
- إنشاء Application واحدة idempotent عند اكتمال الخطوات الثلاث.
- حفظ `submission_snapshot`, نسبة الاكتمال، إصدار الـsnapshot ووقت التقاطه.
- نسخ CV وقت Submit إلى `submitted_cv` مملوكة بالـApplication.
- تخزين CV وصور المشاريع على disk خاص، وإضافة endpoint تنزيل يتحقق من ملكية الموهبة.
- إضافة المجالات الثمانية الموجودة في Figma، واستكمال Job Titles وSkills وTools للمجالات الجديدة.
- إضافة `description` مترجمة لكل Professional Field وإرجاعها في Talent وAdmin APIs.
- تحديث أسماء endpoints وتوليد OpenAPI وPostman مع الـbody والـquery/path parameters والـheaders.
- إضافة أمثلة ثابتة ومقروءة لكل body وquery وpath parameter، بما فيها طلبات `multipart/form-data`، حتى لا تتغير بيانات Apidog عند كل Import.

## الـheaders

كل الطلبات:

```http
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
```

قيمة `X-Authorization` هي مفتاح الـAPI المسجل في جدول `api_keys`، وهي مطلوبة في عمليات التسجيل والدخول والـTalent endpoints.

طلبات رفع CV أو صور المشاريع تستخدم `multipart/form-data` ويضبط العميل الـboundary تلقائيًا.

كل Talent endpoint بعد تسجيل الدخول:

```http
Authorization: Bearer {{access_token}}
```

يمكن إرسال اللغة:

```http
Accept-Language: ar
```

## الحساب والتحقق

الحسابات التجريبية الثابتة التي ينشئها `DemoAccountSeeder`:

```text
Talent: talent@qader.test
Client: client@qader.test
Admin:  admin@qader.test
Password للجميع: Password123!
```

حساب Admin مرتبط بدور `owner`. أمثلة Auth في OpenAPI وPostman تستخدم هذه الحسابات ولا تعتمد على Faker. أمثلة Register تستخدم `new.talent@qader.test` و`new.client@qader.test` لأنها تمثل حسابًا جديدًا.

```text
POST  /api/v1/auth/talent/register
PATCH /api/v1/auth/talent/email-verification
POST  /api/v1/auth/talent/login
GET   /api/v1/auth/me
```

الـbody والـparameters الكاملة لهذه العمليات موجودة في Talent OpenAPI المولد.

## اختيار المجال والحقول

### قائمة المجالات

```http
GET /api/v1/talent/master-data/professional-fields
```

### حفظ المجال

```http
PATCH /api/v1/talent/onboarding/professional-field
```

```json
{
  "professional_field_id": 1
}
```

### input keys الخاصة بالمجال

```http
GET /api/v1/talent/onboarding/form-fields?professional_field_id=1
```

الاستجابة ترجع مصفوفة keys فقط. الفرونت مسؤول عن الخطوة والترتيب والـlabel ونوع الحقل وعلامة required.

## خطوات onboarding

### Basic Info

```http
POST /api/v1/talent/onboarding/personal-information
Content-Type: multipart/form-data
```

الحقول:

```text
professional_field_id
full_name
phone_country_code
phone_number
whatsapp_country_code
whatsapp_number
date_of_birth
gender
religion
nationality_country_id
residence_country_id
residence_city_id
languages[0][language_id]
languages[0][proficiency_level]
cv
```

`email` للعرض فقط ولا يُرسل في الحفظ. CV يقبل PDF/DOC/DOCX حتى 10MB.

### Skills & Experience

```http
POST /api/v1/talent/onboarding/skills-and-experiences
Content-Type: multipart/form-data
```

الحقول الرئيسية:

```text
professional_summary
job_title_id
seniority_level
years_of_experience
skill_ids[]
tool_ids[]
linkedin_url
github_url
behance_url
portfolio_url
experiences[]
projects[]
```

الخبرة:

```text
experiences[0][id]
experiences[0][job_title]
experiences[0][company_name]
experiences[0][company_url]
experiences[0][started_at]
experiences[0][ended_at]
experiences[0][currently_working]
experiences[0][responsibilities]
experiences[0][display_order]
```

المشروع:

```text
projects[0][id]
projects[0][name]
projects[0][description]
projects[0][url]
projects[0][display_order]
projects[0][images][]
projects[0][removed_image_ids][]
```

كل مشروع يقبل حتى 5 صور JPG/PNG، وحد الصورة 5MB.

### Work Preferences وSubmit

```http
POST /api/v1/talent/onboarding/work-preferences
Content-Type: application/json
```

```json
{
  "work_types": ["full_time", "part_time"],
  "work_modes": ["remote", "onsite", "hybrid"],
  "willing_to_relocate": true,
  "notice_period_days": 30,
  "current_salary": 15000,
  "current_currency_id": 1,
  "expected_salary_min": 20000,
  "expected_salary_max": 30000,
  "expected_currency_id": 1
}
```

نجاح هذه العملية بعد اكتمال الخطوتين السابقتين ينشئ Application بحالة `submitted`، ويرجع `application_created=true` عند الإنشاء الأول فقط.

## Draft والقراءة

```text
GET   /api/v1/talent/onboarding
GET   /api/v1/talent/profile
PATCH /api/v1/talent/profile
GET   /api/v1/talent/onboarding/application
```

`PATCH /profile` يقبل الأقسام التالية ويحفظ الحقول المرسلة فقط بدون فرض اكتمال الخطوة:

```text
personal-information
skills-and-experiences
work-preferences
```

## تنزيل الملفات المحمية

```http
GET /api/v1/talent/profile/media/{media}/download
Authorization: Bearer {{access_token}}
```

لا يرجع الملف إلا إذا كان CV للموهبة الحالية، أو صورة مشروع تملكها، أو نسخة CV لتقديمها.

## Master Data المستخدمة في S01

```text
GET /api/v1/talent/master-data/countries
GET /api/v1/talent/master-data/cities?country_id={id}
GET /api/v1/talent/master-data/languages
GET /api/v1/talent/master-data/currencies
GET /api/v1/talent/master-data/professional-fields
GET /api/v1/talent/master-data/job-titles?professional_field_id={id}
GET /api/v1/talent/master-data/skills?professional_field_id={id}
GET /api/v1/talent/master-data/tools?professional_field_id={id}
GET /api/v1/talent/master-data/genders
GET /api/v1/talent/master-data/religions
GET /api/v1/talent/master-data/language-proficiencies
GET /api/v1/talent/master-data/seniority-levels
GET /api/v1/talent/master-data/work-types
GET /api/v1/talent/master-data/work-modes
```

Admin CRUD موجود للدول والمدن واللغات والعملات والمجالات والمسميات والمهارات والأدوات، مع البحث والفلاتر والتفعيل والتعطيل.

## أمثلة كل POST endpoints في S01

كل الأمثلة التالية ترسل:

```http
Accept: application/json
X-Authorization: {{api_key}}
```

أضف `Authorization: Bearer {{access_token}}` لطلبات Talent بعد الدخول، و`Authorization: Bearer {{admin_access_token}}` لطلبات Admin.

### إنشاء حساب موهبة

```http
POST /api/v1/auth/talent/register
Content-Type: application/json
```

```json
{
  "first_name": "New",
  "last_name": "Talent",
  "email": "new.talent@qader.test",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

### دخول الموهبة

```http
POST /api/v1/auth/talent/login
Content-Type: application/json
```

```json
{
  "email": "talent@qader.test",
  "password": "Password123!",
  "fcm_token": "demo-web-fcm-token",
  "platform": "web"
}
```

### حفظ البيانات الشخصية

```http
POST /api/v1/talent/onboarding/personal-information
Content-Type: multipart/form-data
Authorization: Bearer {{access_token}}
```

شكل البيانات كـJSON للتوضيح:

```json
{
  "professional_field_id": 1,
  "full_name": "Ahmed Hassan",
  "phone_country_code": "+20",
  "phone_number": "1012345678",
  "whatsapp_country_code": "+20",
  "whatsapp_number": "1098765432",
  "date_of_birth": "1995-05-20",
  "gender": "male",
  "religion": "islam",
  "nationality_country_id": 1,
  "residence_country_id": 1,
  "residence_city_id": 1,
  "languages": [
    {
      "language_id": 1,
      "proficiency_level": "native"
    }
  ],
  "cv": "@/path/to/cv.pdf"
}
```

قيمة `cv` في المثال تمثل الملف المختار؛ عند التنفيذ تُرسل كحقل File داخل `multipart/form-data`.

الـForm Data المرسل فعليًا:

```text
professional_field_id = 1
full_name = Ahmed Hassan
phone_country_code = +20
phone_number = 1012345678
whatsapp_country_code = +20
whatsapp_number = 1098765432
date_of_birth = 1995-05-20
gender = male
religion = islam
nationality_country_id = 1
residence_country_id = 1
residence_city_id = 1
languages[0][language_id] = 1
languages[0][proficiency_level] = native
cv = @/path/to/cv.pdf
```

### حفظ المهارات والخبرات

```http
POST /api/v1/talent/onboarding/skills-and-experiences
Content-Type: multipart/form-data
Authorization: Bearer {{access_token}}
```

شكل البيانات كـJSON للتوضيح:

```json
{
  "professional_summary": "Backend engineer experienced in building scalable APIs.",
  "job_title_id": 1,
  "seniority_level": "mid_level",
  "years_of_experience": 5,
  "skill_ids": [1],
  "tool_ids": [1],
  "linkedin_url": "https://www.linkedin.com/in/ahmed-hassan",
  "github_url": "https://github.com/ahmed-hassan",
  "portfolio_url": "https://ahmed-hassan.example.com",
  "experiences": [
    {
      "job_title": "Backend Engineer",
      "company_name": "Qader",
      "company_url": "https://qader.example.com",
      "started_at": "2022-01-01",
      "ended_at": "2024-01-01",
      "currently_working": false,
      "responsibilities": "Designed APIs and maintained backend services.",
      "display_order": 1
    }
  ],
  "projects": [
    {
      "name": "Qader Platform",
      "description": "Talent registration platform.",
      "url": "https://project.example.com",
      "display_order": 1,
      "images": ["@/path/to/project.png"]
    }
  ]
}
```

قيم `projects[].images[]` في المثال تمثل الملفات المختارة؛ عند التنفيذ تُرسل كحقول File داخل `multipart/form-data`.

الـForm Data المرسل فعليًا:

```text
professional_summary = Backend engineer experienced in building scalable APIs.
job_title_id = 1
seniority_level = mid_level
years_of_experience = 5
skill_ids[0] = 1
tool_ids[0] = 1
linkedin_url = https://www.linkedin.com/in/ahmed-hassan
github_url = https://github.com/ahmed-hassan
portfolio_url = https://ahmed-hassan.example.com
experiences[0][job_title] = Backend Engineer
experiences[0][company_name] = Qader
experiences[0][company_url] = https://qader.example.com
experiences[0][started_at] = 2022-01-01
experiences[0][ended_at] = 2024-01-01
experiences[0][currently_working] = false
experiences[0][responsibilities] = Designed APIs and maintained backend services.
experiences[0][display_order] = 1
projects[0][name] = Qader Platform
projects[0][description] = Talent registration platform.
projects[0][url] = https://project.example.com
projects[0][display_order] = 1
projects[0][images][0] = @/path/to/project.png
```

`experiences[0][id]`, `projects[0][id]` و`projects[0][removed_image_ids][]` تستخدم عند تحديث سجلات موجودة، ولا تُرسل عند الإنشاء الأول.

### حفظ تفضيلات العمل وإرسال الملف

```http
POST /api/v1/talent/onboarding/work-preferences
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

```json
{
  "work_types": ["full_time", "part_time"],
  "work_modes": ["remote", "hybrid"],
  "willing_to_relocate": true,
  "notice_period_days": 30,
  "current_salary": 15000,
  "current_currency_id": 1,
  "expected_salary_min": 20000,
  "expected_salary_max": 30000,
  "expected_currency_id": 1
}
```

### إنشاء دولة

```http
POST /api/v1/admin/countries
Content-Type: application/json
```

```json
{
  "code": "XZ",
  "name": {"en": "Example Country", "ar": "دولة تجريبية"},
  "is_active": true
}
```

### إنشاء مدينة

```http
POST /api/v1/admin/cities
Content-Type: application/json
```

```json
{
  "country_id": 1,
  "name": {"en": "Example City", "ar": "مدينة تجريبية"},
  "is_active": true
}
```

### إنشاء لغة

```http
POST /api/v1/admin/languages
Content-Type: application/json
```

```json
{
  "code": "x-demo",
  "name": {"en": "Example Language", "ar": "لغة تجريبية"},
  "is_active": true
}
```

### إنشاء عملة

```http
POST /api/v1/admin/currencies
Content-Type: application/json
```

```json
{
  "code": "XQZ",
  "name": {"en": "Example Currency", "ar": "عملة تجريبية"},
  "symbol": "¤",
  "is_active": true
}
```

### إنشاء مجال مهني

```http
POST /api/v1/admin/fields
Content-Type: application/json
```

```json
{
  "name": {"en": "Example Professional Field", "ar": "مجال تجريبي"},
  "description": {
    "en": "Example description for the professional field.",
    "ar": "وصف تجريبي للمجال المهني."
  },
  "is_active": true,
  "form_fields": ["github_url", "portfolio_url"]
}
```

### إنشاء مسمى وظيفي

```http
POST /api/v1/admin/job-titles
Content-Type: application/json
```

```json
{
  "name": "Backend Engineer",
  "professional_field_ids": [1],
  "is_active": true
}
```

### إنشاء مهارة

```http
POST /api/v1/admin/skills
Content-Type: application/json
```

```json
{
  "name": "Laravel",
  "professional_field_ids": [1],
  "is_active": true
}
```

### إنشاء أداة

```http
POST /api/v1/admin/tools
Content-Type: application/json
```

```json
{
  "name": "Docker",
  "professional_field_ids": [1],
  "is_active": true
}
```

## استيراد Apidog

بعد تشغيل:

```bash
php artisan migrate
php artisan db:seed
php artisan scribe:generate
```

اربط Apidog بمصدر Talent التالي:

```text
http://localhost:8000/docs/talent.openapi
```

والمصادر الأخرى:

```text
http://localhost:8000/docs/client.openapi
http://localhost:8000/docs/admin.openapi
```

فعل المزامنة من URL داخل Apidog حتى يعيد الاستيراد من نفس المصدر بعد كل `scribe:generate`. ملف Postman الكامل موجود محليًا في:

```text
storage/app/private/scribe/collection.json
```

## التحقق المنفذ

- نجح فحص syntax لكل ملفات PHP.
- ظهرت كل Talent routes الجديدة في `route:list`.
- نجح `migrate:fresh --seed` على SQLite مؤقت داخل حاوية المشروع بعد تحديث migrations الإنشاء الأصلية، بما يشمل كل seeders.
- نجح `scribe:generate` داخل حاوية PHP الخاصة بالمشروع.
- تم فحص Talent OpenAPI والتأكد من وجود request bodies، الملفات، query/path parameters، security scheme، وتقسيم Apidog إلى `talent/*`.
