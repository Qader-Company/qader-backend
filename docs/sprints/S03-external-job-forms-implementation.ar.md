# S03 — تنفيذ النماذج الخارجية

تاريخ التنفيذ: 22 أكتوبر 2026.

## ما تم تنفيذه

- إدارة Job Forms من بوابة الأدمن: القائمة والبحث والفلترة والإنشاء والعرض والتعديل والنشر والتفعيل والتعطيل والحذف المرن.
- إنشاء عنوان النموذج و`slug` من المسمى الوظيفي المختار.
- حفظ تفاصيل الوظيفة وأقسام `responsibility` و`requirement` و`qualification`.
- اختيار الأسئلة الإضافية من `professional_form_fields` المفعلة للمجال نفسه، بنفس عقد المفاتيح المستخدم في النماذج الداخلية.
- نسخة منشورة immutable داخل `job_form_versions`. عند تعديل نموذج منشور ينشأ Draft جديد، وتظل النسخة المنشورة الحالية هي الظاهرة للعامة حتى نشر النسخة الجديدة.
- قائمة الوظائف العامة وتفاصيل الوظيفة ونموذج التقديم العام.
- استقبال تقديم Guest حقيقي مع CV على private disk وإجابات ديناميكية مرتبطة بنسخة النموذج المنشورة.
- منع تكرار نفس الطلب الشبكي باستخدام `idempotency_key`، مع السماح لنفس البريد بالتقديم أكثر من مرة بمفاتيح مختلفة.
- قائمة تقديمات الأدمن والبحث والفلترة وCSV Export والعرض وتنزيل CV والحذف الفردي.
- `Start Hiring Process`: ربط حساب Talent موجود أو إنشاء حساب جديد بكلمة مرور مؤقتة، وإرسال بريد الدخول، وربط بيانات المجال والمسمى والهاتف.
- الحساب المنشأ من التقديم يجب أن يغير كلمة المرور في أول Login، ويحصل على `reset_password_token` بدل access token. Reset Password يلغي هذا الشرط.
- Rate limit للتقديم العام: 5 طلبات في الدقيقة للوظيفة من الـIP و30 طلبًا في الساعة عبر الوظائف من الـIP.
- صلاحيات مستقلة لكل مجموعة أكشنز، وتم ضمها إلى مزامنة صلاحيات الأدمن الحالية.

## الجداول

المهاجرة الجديدة:

`database/migrations/2026_10_30_000000_create_external_job_form_tables.php`

تنشئ:

- `job_postings`
- `job_posting_sections`
- `job_form_versions`
- `job_form_fields`
- `job_form_submissions`
- `job_form_answers`

وتضيف `users.must_change_password` لدعم كلمة المرور المؤقتة. تم تحديث `docs/database-schema-proposal.dbml` بالشكل المنفذ وإزالة الجداول القديمة غير المعتمدة مثل `job_forms` و`job_question_catalog` و`job_posting_skills`.

## الصلاحيات

- `admin.job-forms.view`
- `admin.job-forms.manage`
- `admin.job-form-submissions.view`
- `admin.job-form-submissions.delete`
- `admin.job-form-submissions.start-hiring`

بعد تطبيق المهاجرة شغّل داخل Docker:

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=AdminRoleSeeder --force
```

## Headers

كل endpoints:

```text
Accept: application/json
X-Authorization: {{api_key}}
```

Admin endpoints فقط:

```text
Authorization: Bearer {{access_token}}
```

Public Job endpoints لا تحتاج Bearer token.

## Admin Job Forms

Base URL: `/api/v1/admin/job-forms`

| Method | Endpoint | الوظيفة |
| --- | --- | --- |
| GET | `/` | القائمة والبحث والفلترة |
| GET | `/publishing-statuses` | حالات النشر |
| GET | `/submission-statuses` | حالات التقديم |
| GET | `/work-types` | أنواع العمل |
| GET | `/work-modes` | أنماط العمل |
| GET | `/experience-ranges` | نطاقات الخبرة |
| POST | `/` | إنشاء Draft أو نشر مباشر |
| GET | `/{jobForm}` | تفاصيل النموذج |
| PATCH | `/{jobForm}` | تعديل النموذج أو إنشاء إصدار جديد |
| POST | `/{jobForm}/publish` | نشر الـDraft الحالي |
| PATCH | `/{jobForm}/activation` | تفعيل أو تعطيل الرابط |
| DELETE | `/{jobForm}` | Soft delete |

`jobForm` هو الـslug مثل `backend-engineer-a1b2c3d4`.

مثال إنشاء أو تعديل JSON:

```json
{
  "professional_field_id": 1,
  "job_title_id": 1,
  "summary": "Build reliable backend services for Qader products.",
  "about_role": "You will own APIs and collaborate with product and frontend teams.",
  "work_type": "full_time",
  "work_mode": "hybrid",
  "publishing_status": "published",
  "sections": [
    {
      "section": "responsibility",
      "items": ["Design and maintain APIs.", "Review backend changes."]
    },
    {
      "section": "requirement",
      "items": ["Strong PHP and Laravel experience."]
    },
    {
      "section": "qualification",
      "items": ["Good written and verbal communication."]
    }
  ],
  "professional_form_field_ids": [12, 23, 38]
}
```

مثال التفعيل:

```json
{
  "is_active": true
}
```

`POST /{jobForm}/publish` لا يحتاج body.

## Admin Submissions

| Method | Endpoint | الوظيفة |
| --- | --- | --- |
| GET | `/{jobForm}/submissions` | القائمة والبحث والفلترة |
| GET | `/{jobForm}/submissions/export` | تنزيل CSV بنفس الفلاتر |
| GET | `/{jobForm}/submissions/{submission}` | التفاصيل والإجابات |
| GET | `/{jobForm}/submissions/{submission}/cv` | تنزيل CV |
| DELETE | `/{jobForm}/submissions/{submission}` | حذف تقديم `received` فقط |
| POST | `/{jobForm}/submissions/{submission}/start-hiring` | بدء التوظيف وإنشاء أو ربط Talent |

`start-hiring` لا يحتاج body، وهو idempotent؛ تكراره لا ينشئ حسابًا أو يرسل بريدًا ثانيًا.

فلاتر القائمة وCSV:

- `search`
- `status`: `received` أو `hiring_started`
- `submitted_from`
- `submitted_to`
- `per_page` للقائمة

## Public Jobs

Base URL: `/api/v1/public/jobs`

| Method | Endpoint | الوظيفة |
| --- | --- | --- |
| GET | `/` | Explore Open Positions |
| GET | `/filters` | اختيارات المجال والمهارات ونوع الوظيفة ونمط العمل ونطاق الخبرة |
| GET | `/{job}` | تفاصيل الوظيفة المنشورة |
| GET | `/{job}/application-form` | الحقول الثابتة والمفاتيح والخيارات |
| POST | `/{job}/submissions` | إرسال التقديم |

فلاتر القائمة:

- `search`: يبحث في العنوان والملخص ومهارات المجال.
- `professional_field_id`
- `skill_id`
- `work_type`
- `work_mode`
- `per_page`

استجابة القائمة والتفاصيل ترجع `skills` الفعالة المرتبطة بمجال الوظيفة لعرضها في البطاقات والـsidebar كما في Figma. المجالات والمهارات في `/filters` مقصورة على البيانات المرتبطة بوظائف منشورة وفعالة، بينما `work_types` و`work_modes` و`experience_ranges` تأتي من الـenums. نطاق الخبرة متاح للواجهة حاليًا، لكن تطبيقه على نتائج الوظائف مؤجل حتى تخزن الوظيفة نطاق الخبرة.

قيمة `public_url` تُبنى من `APP_URL` ثم `/jobs/{slug}`.

### Form Data للتقديم

| Key | النوع | مطلوب |
| --- | --- | --- |
| `applicant_name` | text | نعم |
| `email` | text | نعم |
| `phone_country_code` | text | نعم |
| `phone_number` | text | نعم |
| `cv` | file: PDF/DOC/DOCX، حتى 10MB | نعم |
| `consent` | boolean | نعم ويجب أن تكون true |
| `idempotency_key` | UUID | نعم |
| `answers` | object | حسب الحقول المختارة |

تمثيل `answers` في multipart يكون JSON object. مثال:

```json
{
  "years_of_experience": "3_to_5",
  "notice_period_days": "30 days",
  "skill_ids": [1, 2],
  "expected_salary": {
    "amount": 25000,
    "currency_id": 1
  }
}
```

كل حقل إضافي مختار مطلوب في التقديم. `GET application-form` يرجع الخيارات اللازمة للمهارات والأدوات والعملات والدول والمدن واللغات والـenums.

## تطبيق قاعدة البيانات وتحديث Apidog

داخل Docker وبالترتيب:

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan migrate --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=AdminRoleSeeder --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

ثم حدّث مصدر Apidog الخاص بموقع المنصة من:

```text
http://localhost:8000/docs/website.openapi
```

المسارات العامة موجودة داخل Website OpenAPI في فولدر `website/jobs`، ومنفصلة عن Admin OpenAPI. يحتفظ الملف المولد بـ`X-Authorization: {{api_key}}` و`Accept: application/json`، ولا يحتاج Bearer Token لأن الزائر لا يسجل الدخول.

## التحقق المنفذ

- نجحت كل migrations من الصفر على SQLite معزولة داخل Docker، ومنها مهاجرة S03.
- نجح `DatabaseSeeder` بعد إضافة الصلاحيات الجديدة.
- نجح `scribe:generate` داخل Docker.
- تم التحقق أن `admin.openapi` يحتوي إدارة Job Forms، وأن `website.openapi` يحتوي Public Jobs الخمسة، مع bodies والquery/path parameters وmultipart والheaders بقيم Apidog المطلوبة.
