# S02 — توثيق تنفيذ تقييم المواهب

الحالة: **تم تنفيذ Backend السبرنت وتدفق الموهبة المقابل**
تاريخ آخر تحديث: 21 أكتوبر 2026

## ما تم تنفيذه

- قائمة طلبات المواهب مع `All Applications` و`My Applications` والبحث والفلاتر والترتيب والـpagination.
- إحصائيات `all`, `pending`, `accepted`, `rejected` في `meta.summary`.
- تفاصيل الطلب من `submission_snapshot` ونسخة CV المرسلة وقت التقديم.
- قائمة المراجعين النشطين الذين لديهم صلاحية التقييم.
- Assign وReassign الفردي والجماعي مع حفظ تاريخ التعيينات.
- التعيين الذاتي عند الضغط على Evaluate لطلب غير معيّن.
- تقييم كامل بأربعة معايير من 1 إلى 5، مع الراتب والتقارير والنصائح الاختيارية.
- رفض الطلب مع إعادة تقديم بعد مدة أو إدخاله في blacklist.
- إنشاء تفاصيل المقابلة والمقابلين والـslots اليدوية.
- مدة slot افتراضية 60 دقيقة؛ الواجهة ترسل `start` والسيرفر يحسب `end`.
- منع تداخل فترة أي interviewer مع مقابلة قائمة.
- سجل للأحداث الأساسية.
- `available_actions` في استجابة التفاصيل حتى يعرض الفرونت الأزرار المسموحة.
- Admin OpenAPI وPostman محدثان بأسماء واضحة وأمثلة ثابتة.

## تدفق الموهبة المقابل للتقييم والمقابلة

تمت مراجعة حالات الويب والموبايل في Figma وتنفيذ العقد الذي تحتاجه الشاشات:

- `under_review`: الطلب قيد المراجعة والملف المرسل ثابت.
- `rejected`: الرفض مع `reapply_at` وحالة blacklist دون كشف السبب الداخلي.
- `interview_slot_required`: عرض المواعيد المتاحة واختيار موعد واحد.
- `awaiting_new_interview_slots`: انتظار مواعيد بديلة بعد رفض المواعيد أو إلغاء الموعد المؤكد.
- `interview_confirmed`: الموعد المختار ورابط Google Meet للمقابلة الأونلاين.
- `interview_completed`: حالة جاهزة لعرض اكتمال المقابلة عندما يحدّثها الأدمن لاحقًا.
- `accepted`: حالة القبول النهائي الجاهزة للعرض عندما ينفذ قرارها.

الـendpoint الأساسي للشاشة:

```http
GET /api/v1/talent/application
```

يرجع `screen_state` وبيانات الطلب و`interview` و`available_slots` و`selected_slot` و`actions` وFAQs المناسبة للحالة الحالية.

عمليات الموهبة:

```text
POST /api/v1/talent/application/interview/slots/{slot}/select
POST /api/v1/talent/application/interview/slots/reject
POST /api/v1/talent/application/interview/cancel
```

- رفض كل المواعيد يحتاج `reason` من 10 إلى 1000 حرف.
- الموهبة تستطيع رفض مجموعات المواعيد مرتين فقط. الاستجابة ترجع `slot_rejections.used`, `maximum`, `remaining`.
- إلغاء الموعد المؤكد وإعادة الجدولة متاح مرة واحدة فقط حسب التصميم.
- عند اختيار slot أونلاين ينشئ السيرفر Google Calendar Event وGoogle Meet، ثم يحفظ `google_calendar_event_id` و`meeting_url`.
- عند فشل Google Calendar لا يثبت السيرفر اختيار الموعد، وتبقى البيانات في حالتها السابقة.

بعد الرفض أو الإلغاء يرسل الأدمن مواعيد بديلة من:

```http
POST /api/v1/admin/talent-applications/{application}/interview/slots
```

ويستخدم نفس شكل `slots` في جدولة المقابلة الأصلية مع الإبقاء على عداد مرات الرفض.

## FAQs الخاصة بمراجعة الموهبة

FAQs الظاهرة في شاشات Figma أضيفت كبيانات مترجمة يديرها الأدمن. كل سؤال يحدد `screen_states` التي يظهر فيها و`display_order` و`is_active`.

```text
GET    /api/v1/admin/talent-review-faqs/screen-states
GET    /api/v1/admin/talent-review-faqs
GET    /api/v1/admin/talent-review-faqs/{faq}
POST   /api/v1/admin/talent-review-faqs
PATCH  /api/v1/admin/talent-review-faqs/{faq}
DELETE /api/v1/admin/talent-review-faqs/{faq}
```

مثال الإنشاء والتعديل:

```json
{
  "screen_states": ["under_review", "rejected"],
  "question": {
    "en": "How long does the review process take?",
    "ar": "كم تستغرق عملية المراجعة؟"
  },
  "answer": {
    "en": "You will be notified as soon as your application moves to the next stage.",
    "ar": "سيتم إشعارك بمجرد انتقال طلبك إلى المرحلة التالية."
  },
  "display_order": 1,
  "is_active": true
}
```

`TalentReviewFaqSeeder` يضيف المحتوى الأساسي الموجود في التصميم بطريقة قابلة لإعادة التشغيل دون تكرار البيانات.

## Endpoints قيم تقييم الطلب والمقابلة

كل enum لها endpoint مستقلة داخل موديول `talent-applications` حتى يستخدمها الفرونت في الفلاتر والنماذج دون تثبيت القيم داخله:

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
```

كل استجابة ترجع مصفوفة من `{value, label}`، ويحدد `Accept-Language` لغة `label`.

## إعداد Google Calendar وMeet

```dotenv
GOOGLE_CALENDAR_CREDENTIALS=/var/www/html/storage/app/private/google-service-account.json
GOOGLE_CALENDAR_ID=
GOOGLE_CALENDAR_SUBJECT=
```

- يمكن وضع نفس مسار `FIREBASE_CREDENTIALS` في `GOOGLE_CALENDAR_CREDENTIALS` إذا كان نفس Service Account مخولًا للوصول إلى Calendar API.
- يجب تفعيل Google Calendar API ومشاركة التقويم مع بريد الـService Account.
- `GOOGLE_CALENDAR_SUBJECT` يستخدم فقط عند إعداد Domain-Wide Delegation لانتحال مستخدم Google Workspace مخول بإنشاء Meet.

## تطبيق قاعدة البيانات على السيرفر

لا تستخدم `migrate:fresh` لأن السيرفر يحتوي بيانات.

شغّل بالترتيب:

```bash
php artisan migrate --force
php artisan db:seed --class=AdminRoleSeeder --force
php artisan db:seed --class=TalentReviewFaqSeeder --force
php artisan optimize:clear
```

إذا كنت تستخدم Maintenance API:

```http
POST /api/maintenance/migrate
POST /api/maintenance/seed
Content-Type: application/json

{
  "seeder": "TalentReviewFaqSeeder"
}
```

نفّذ Maintenance seed مرة لـ`AdminRoleSeeder` ومرة لـ`TalentReviewFaqSeeder`. الـmigrations تنشئ الجداول غير الموجودة وتضيف أعمدة المراجعة من غير حذف الطلبات الحالية.

## الجداول

- `talent_applications`: المراجع الحالي، منفذ القرار، وقت القرار، وموعد السماح بإعادة التقديم.
- `talent_application_reviewer_assignments`: تاريخ Assign وReassign.
- `talent_evaluations`: التقييم والتقارير وتوصية الراتب.
- `evaluation_tips`: نصائح التقييم.
- `talent_blacklist_entries`: سبب الحظر ومن نفذه ووقت تنفيذه.
- `interviews`: نوع المقابلة وحالتها وحقول Google المستقبلية.
- `interview_admin`: المقابلون.
- `interview_slots`: `starts_at`, `ends_at`, النوع والموقع والحالة.
- `talent_application_events`: سجل العمليات الأساسية.
- `talent_review_faqs`: الشاشات المرتبطة بالسؤال وترتيبه وحالة تفعيله.
- `talent_review_faq_translations`: السؤال والإجابة بالعربية والإنجليزية.

## الهيدرز

كل endpoints التالية تحتاج:

```http
X-Authorization: {{api_key}}
Authorization: Bearer {{access_token}}
Accept: application/json
Content-Type: application/json
```

`Content-Type` غير مطلوب في طلبات GET.

## Endpoints القراءة

```text
GET /api/v1/admin/talent-applications
GET /api/v1/admin/talent-applications/reviewers
GET /api/v1/admin/talent-applications/{application}
GET /api/v1/admin/talent-applications/{application}/cv
GET /api/v1/admin/talent-applications/{application}/reviewer-history
GET /api/v1/admin/talent-applications/{application}/activity
```

Query parameters للقائمة:

```text
scope=all|my
search=software
status[]=submitted
status[]=under_review
reviewer_admin_id=2
submitted_from=2026-10-01
submitted_to=2026-10-31
sort=newest|oldest
per_page=15
```

## Assign أو Reassign

```http
POST /api/v1/admin/talent-applications/assign-reviewer
```

```json
{
  "application_ids": [1, 2],
  "reviewer_admin_id": 4
}
```

العملية جماعية وذرية؛ إذا كان أي طلب أو المراجع غير صالح لا يتم تعديل أي طلب.

## بدء التقييم

```http
POST /api/v1/admin/talent-applications/1/start-review
```

لا يوجد body. إذا لم يكن للطلب مراجع، يُعيّن المستخدم الحالي تلقائيًا. إذا كان معيّنًا لمراجع آخر ترجع الاستجابة `409`.

## حفظ التقييم

```http
PUT /api/v1/admin/talent-applications/1/evaluation
```

```json
{
  "technical_skills_rating": 4,
  "communication_skills_rating": 5,
  "problem_solving_rating": 4,
  "soft_skills_rating": 3,
  "recommended_salary_min": 20000,
  "recommended_salary_max": 30000,
  "currency_id": 1,
  "hr_report": "Strong communication and clear career progression.",
  "technical_report": "Meets the expected technical level.",
  "psychology_report": null,
  "tips": [
    {
      "title": "Improve system design",
      "priority": "high",
      "description": "Practice explaining architecture trade-offs."
    }
  ]
}
```

المعايير الأربعة مطلوبة دائمًا. حقول الراتب الثلاثة تُرسل معًا أو لا تُرسل. إرسال `tips: []` يحذف النصائح السابقة.

## الرفض مع إعادة التقديم

```http
POST /api/v1/admin/talent-applications/1/reject
```

```json
{
  "reapply_policy": "allowed_after_period",
  "reapply_after_months": 6
}
```

السيرفر يحسب `reapply_at` من وقت الرفض.

## الرفض مع Blacklist

```json
{
  "reapply_policy": "blacklisted",
  "blacklist_reason": "Repeated submission of invalid information."
}
```

`blacklist_reason` مطلوب فقط مع `blacklisted`.

## جدولة المقابلة

```http
POST /api/v1/admin/talent-applications/1/schedule-interview
```

```json
{
  "type": "technical",
  "interviewer_admin_ids": [4, 7],
  "slots": [
    {
      "mode": "online",
      "start": "2026-11-01T12:00:00+02:00"
    },
    {
      "mode": "offline",
      "start": "2026-11-02T15:00:00+02:00",
      "location": "Qader Office, Giza",
      "latitude": 30.0444200,
      "longitude": 31.2357120
    }
  ]
}
```

الواجهة لا ترسل `end`. السيرفر يحسبها بعد 60 دقيقة ويعيد كل slot بالشكل:

```json
{
  "start": "2026-11-01T10:00:00.000000Z",
  "end": "2026-11-01T11:00:00.000000Z"
}
```

عند وجود تعارض ترجع الاستجابة `422` وبداخل `errors.conflicts` الـslot والمقابلون المتعارضون. لا يتم إنشاء أي جزء من المقابلة.

## الصلاحيات

```text
admin.talent-applications.view
admin.talent-applications.assign-reviewer
admin.talent-applications.review
admin.talent-applications.decide
```

- دور `reviewer`: `view`, `review`, `decide`.
- دور `admin`: الصلاحيات الأربع.
- أي role إضافي يعمل حسب الصلاحيات الممنوحة له من Super Admin.

## الحالات المهمة

- `422`: Validation أو تعارض مواعيد.
- `403`: لا يملك المستخدم الصلاحية.
- `404`: الطلب أو CV غير موجود.
- `409`: الطلب معيّن لمراجع آخر، مغلق، أو تم اتخاذ قرار عليه بالفعل.

## توثيق Apidog

بعد نشر الكود وتشغيل Scribe:

```bash
php artisan scribe:generate
```

استورد أو حدّث مصدر Admin من:

```text
http://localhost:8000/docs/admin.openapi
```

الـendpoints تظهر داخل:

```text
admin / talent-applications
```

ملف OpenAPI يحتوي Bearer Auth و`X-Authorization` وكل query/path/body parameters وأمثلة JSON كاملة.

## التحقق المنفذ

- تشغيل كل migrations بنجاح على SQLite منفصلة، مع نسخة اختبار محلية متجاهلة من Git في `database/testing.sqlite`.
- نجاح دورة `migrate:fresh` ثم rollback لـS02 ثم migrate مرة أخرى.
- نجاح اختبارات تدفق الموهبة والمقابلة: 4 tests و19 assertions، وتشمل منع الرفض الثالث واستخدام endpoint المواعيد البديلة.
- نجاح فحص PHP syntax.
- نجاح Laravel Pint.
- ظهور 27 route للإدارة خاصة بـS02 مع الـmiddleware والصلاحيات الصحيحة، تشمل العمليات وFAQs وendpoints قيم enums.
- نجاح `scribe:generate` ومراجعة Admin OpenAPI والأمثلة المولدة.
