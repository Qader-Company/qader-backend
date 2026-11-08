# S02B — توثيق تنفيذ استكمال المقابلات والقرار النهائي

الحالة: **تم تنفيذ Backend السبرنت**  
تاريخ التنفيذ: 26 أكتوبر 2026

## النتيجة

أصبحت دورة طلب الموهبة مكتملة من المقابلة المؤكدة حتى أحد القرارات النهائية:

```text
confirmed → in_progress → completed → evaluated
                                      ├─ accepted
                                      ├─ rejected
                                      └─ another interview round
```

كما أصبحت إدارة المواهب المقبولة والمرفوضة والـBlacklist جزءًا مستقلًا في Admin Portal، مع `available_actions` محسوبة حسب الحالة وصلاحيات الأدمن.

## التوافق مع الـFrontend الحالي

- لم يتغير Path أو HTTP Method لأي endpoint موجود قبل S02B.
- لم يُحذف أو يُعاد تسمية أي حقل قديم من Responses الموهبة.
- يظل `status` موجودًا، وأضيف بجانبه `application_status` و`effective_status`.
- يظل `interview` موجودًا بنفس الاسم، وأضيف بجانبه `current_interview` و`interview_history`.
- يظل `meeting_url` متاحًا في حالتي `confirmed` و`in_progress`، بينما `actions.can_join` هي التي تتحكم في ظهور زر Join حسب نافذة الوقت.
- تعديل middleware على Reject وSchedule Interview وسّع الصلاحيات المسموحة فقط، ولم يغير الـURL أو Body أو Response contract.

## قاعدة البيانات

تم تعديل migration الإنشاء الموجودة:

```text
database/migrations/2026_10_27_000000_create_talent_evaluation_tables.php
```

التغييرات:

- دعم أكثر من مقابلة للطلب عبر `round_number` وUnique على `(application_id, round_number)`.
- إضافة `started_at` و`completed_at` إلى `interviews`.
- إضافة جدول `interview_evaluations` بتقييم كامل من 1 إلى 5 وتقارير و`final_feedback`.
- أحداث الانتقال الزمني التلقائي تُنسب إلى المسؤول المعيّن للطلب، مع `transition_source = automatic` للتمييز عن الضغط اليدوي.

كما أضيفت Upgrade Migration لتطبيق نفس التغييرات على قواعد البيانات التي شغّلت Migration الإنشاء القديمة بالفعل:

```text
database/migrations/2026_11_01_000000_upgrade_s02b_talent_interview_completion_schema.php
```

الـUpgrade Migration تتحقق من وجود كل جدول وعمود وIndex قبل تعديله، ولذلك تعمل بأمان أيضًا بعد `migrate:fresh` بدون تكرار الـschema.

## المزامنة التلقائية

- `confirmed` تتحول إلى `in_progress` عند بداية الموعد.
- `in_progress` تتحول إلى `completed` عند نهاية الموعد.
- المزامنة تعمل كل دقيقة من Laravel Scheduler.
- تتم المزامنة أيضًا عند قراءة المقابلة أو تنفيذ Action، فلا تعتمد صحة الحالة على الـScheduler وحده.
- Start وComplete متاحان يدويًا وIdempotent.
- نافذة Join تبدأ قبل الموعد بـ15 دقيقة وتنتهي عند `ends_at`.
- `My Interviews` تعتمد على المسؤول المعيّن في `talent_applications.assigned_admin_id`.
- Start وComplete وEvaluate والقرار النهائي لا ينفذها إلا المسؤول عن الطلب مع الصلاحية المطلوبة؛ بقية الأدمن يمكنهم العرض فقط.

يجب أن يكون Laravel Scheduler مفعّلًا على السيرفر.

## الصلاحيات الجديدة

```text
admin.interviews.view
admin.interviews.manage
admin.interviews.evaluate
admin.interviews.decide
admin.talents.view
admin.talents.manage-blacklist
admin.talents.manage-reapply
```

جميع Endpoints تحتاج:

```http
X-Authorization: {{api_key}}
Authorization: Bearer {{access_token}}
Accept: application/json
```

ويضاف في الطلبات التي لها Body:

```http
Content-Type: application/json
```

## Endpoints المقابلات

```text
GET  /api/v1/admin/interviews
GET  /api/v1/admin/interviews/{interview}
POST /api/v1/admin/interviews/{interview}/start
POST /api/v1/admin/interviews/{interview}/complete
PUT  /api/v1/admin/interviews/{interview}/evaluation
```

فلاتر القائمة:

```text
scope=all|my
search=Ahmed
status[]=confirmed
type[]=technical
interviewer_admin_id=2
scheduled_from=2026-11-01
scheduled_to=2026-11-30
sort=newest|oldest
per_page=20
```

مثال تقييم المقابلة:

```json
{
  "technical_skills_rating": 4,
  "communication_skills_rating": 5,
  "problem_solving_rating": 4,
  "soft_skills_rating": 3,
  "hr_report": "Strong communication and ownership.",
  "technical_report": "Meets the expected technical level.",
  "psychology_report": null,
  "final_feedback": "The candidate meets the requirements and is recommended to continue."
}
```

## القرار النهائي والجولة التالية

```text
POST /api/v1/admin/talent-applications/{application}/accept
POST /api/v1/admin/talent-applications/{application}/reject
POST /api/v1/admin/talent-applications/{application}/schedule-interview
```

Accept لا يحتوي Body. يشترط أن تكون آخر مقابلة مكتملة ومقيّمة.

مثال Reject بعد المقابلة:

```json
{
  "reapply_policy": "allowed_after_period",
  "reapply_after_months": 6
}
```

أو Blacklist أثناء الرفض:

```json
{
  "reapply_policy": "blacklisted",
  "blacklist_reason": "Repeated policy violations."
}
```

استخدام `schedule-interview` بعد مقابلة مكتملة ومقيّمة ينشئ Round جديدة، مع نفس Body الجدولة المستخدم في S02.

## إدارة حالات المواهب

```text
GET   /api/v1/admin/talents
GET   /api/v1/admin/talents/{talent}
POST  /api/v1/admin/talents/{talent}/add-to-blacklist
POST  /api/v1/admin/talents/{talent}/remove-from-blacklist
PATCH /api/v1/admin/talents/{talent}/edit-reapply
```

فلاتر القائمة:

```text
state[]=accepted|rejected|blacklisted
rejection_stage=before_interview|after_interview
search=Ahmed
sort=newest|oldest
per_page=20
```

Add to Blacklist:

```json
{
  "blacklist_reason": "Repeated policy violations."
}
```

Remove from Blacklist لموهبة مقبولة بدون Body. للموهبة المرفوضة:

```json
{
  "reapply_after_months": 6
}
```

Edit Reapply:

```json
{
  "reapply_after_months": 6
}
```

الطلب يحتفظ بنتيجته التاريخية في `application_status`، بينما تصبح `effective_status` مساوية لـ`blacklisted` عند وجود حظر فعال.

## Talent API

```text
GET /api/v1/talent/application
```

أصبحت الاستجابة ترجع:

- `application_status` و`effective_status`.
- `current_interview` للتوافق المباشر مع الشاشة الحالية.
- `interview_history` لكل الجولات.
- تقييم كل جولة مكتملة وتقاريرها و`final_feedback`.
- `join_window_starts_at`, `join_window_ends_at` و`actions.can_join`.
- حالات الشاشة `interview_in_progress` و`blacklisted` بالإضافة للحالات السابقة.

## `available_actions`

في Responses الأدمن تُحسب الأفعال من الحالة والصلاحيات، ومن أمثلتها:

```text
start
complete
evaluate
update_evaluation
accept
reject
schedule_another_interview
add_to_blacklist
remove_from_blacklist
edit_reapply
```

## تحديث السيرفر

لا تستخدم `migrate:fresh` على قاعدة تحتوي بيانات. لتحديث قاعدة السيرفر الحالية شغّل:

```bash
php artisan migrate --force
php artisan db:seed --class=AdminRoleSeeder --force
php artisan optimize:clear
```

وتأكد من تشغيل Scheduler:

```bash
php artisan schedule:run
```

مرة كل دقيقة بواسطة Cron أو Process Manager.

## تحديث Apidog محليًا

داخل Container المشروع:

```bash
php artisan optimize:clear
php artisan scribe:generate
```

ثم أعد Import من:

```text
http://localhost:8000/docs/admin.openapi
http://localhost:8000/docs/talent.openapi
```

