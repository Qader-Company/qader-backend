# S06 — تنفيذ المقابلات والعروض

تم تنفيذ دورة العرض والتفاوض ومقابلة التوظيف وقرار العميل لكل عنصر داخل Hiring Request، مع فصلها بالكامل عن مقابلات تقييم انضمام الموهبة في S02.

## ما تم تنفيذه

- قائمة عروض التوظيف وتفاصيلها للموهبة.
- البحث وفلترة قائمة عروض الموهبة حسب حالة العنصر وحالة العرض، مع Endpoint مستقل لقيم الفلاتر.
- قبول العرض ورفضه وإرسال Counter Offer.
- إرسال الأدمن المسؤول عرضًا معدلًا بعد التنسيق الخارجي مع العميل.
- تسجيل الأدمن قبول أورفض العرض بالنيابة، بما في ذلك العرض المنتهي.
- انتهاء العرض تلقائيًا بعد المهلة وتحويله إلى `expired`.
- زيادة `offer_no_response_count` وتعطيل حساب الموهبة عند بلوغ الحد الافتراضي `2`، دون غلق عنصر الطلب.
- حفظ `notice_period_days` و`work_location` كـSnapshot مستقل داخل كل نسخة عرض.
- مقابلة Hiring مستقلة بموعد Online واحد ومدته الافتراضية 60 دقيقة.
- رد مستقل للعميل والموهبة: `accepted`, `change_requested`, `apologized`.
- طلب تغيير الموعد أوالاعتذار بعد تأكيده وقبل بدايته، ثم إعادة الجدولة من الأدمن مع حفظ المحاولة السابقة في Events.
- تأكيد الأدمن للموعد بعد موافقة الطرفين، مع Google Meet أورابط يدوي.
- منع تداخل مقابلات الموهبة أوممثل العميل المسجل في الطلب.
- انتقال المقابلة تلقائيًا أويدويًا إلى `in_progress` ثم `completed`.
- ظهور رابط Join فقط من بداية الموعد حتى نهايته.
- قرار العميل بعد المقابلة: `proceed` أو`reject`، كما يظهر في Figma.
- انتقال العنصر مباشرة إلى `accepted` عند `proceed` أو`rejected` عند `reject`.
- لا يوجد قرار بعد المقابلة للموهبة، ولا يستطيع العميل سحب قراره بعد إرساله؛ التصحيح التشغيلي متاح للأدمن المسؤول فقط.
- Hold وResume وCancel للعميل والأدمن، مع إلغاء المقابلة المؤكدة وبدء مهلة كاملة جديدة عند Resume.
- Audit Events لكل تغيير جوهري و`available_actions` حسب البورتال والحالة والصلاحية.
- Scheduled jobs لانتهاء العروض وتحديث حالات المقابلات كل دقيقة.

## السكيما

Migration:

```text
database/migrations/2026_11_05_000000_create_s06_hiring_interviews_and_offer_workflow.php
database/migrations/2026_11_10_000000_remove_talent_hiring_decisions.php
```

التغييرات:

- جدول جديد `hiring_interviews`، صف واحد حالي لكل `hiring_request_item`.
- إضافة `proposed_by`, `notice_period_days`, `work_location` إلى `hiring_offers`.
- إضافة قرار العميل و`decision_due_at` إلى `hiring_request_items`.
- إزالة أعمدة قرار الموهبة التي لم تظهر في Figma من السكيما المطبقة سابقًا.
- إضافة `offer_no_response_count` إلى `talents`.
- السماح بأن يكون `hiring_request_events.actor_user_id` فارغًا للأحداث التلقائية فقط.

المواعيد السابقة وإعادة الجدولة لا تحتاج جدول Slots؛ تحفظ تفاصيل المحاولة السابقة في `hiring_request_events` قبل استبدال الموعد الحالي.

## Endpoints الموهبة

```text
GET    /api/v1/talent/hiring-offers
GET    /api/v1/talent/hiring-offers/options
GET    /api/v1/talent/hiring-offers/{item}
POST   /api/v1/talent/hiring-offers/{item}/accept
POST   /api/v1/talent/hiring-offers/{item}/reject
POST   /api/v1/talent/hiring-offers/{item}/counter
GET    /api/v1/talent/hiring-interviews/{interview}
POST   /api/v1/talent/hiring-interviews/{interview}/accept-schedule
POST   /api/v1/talent/hiring-interviews/{interview}/request-schedule-change
POST   /api/v1/talent/hiring-interviews/{interview}/apologize
```

## Endpoints العميل داخل Workspace

```text
GET    /api/v1/client/workspaces/{workspace}/hiring-interviews/{interview}
POST   /api/v1/client/workspaces/{workspace}/hiring-interviews/{interview}/accept-schedule
POST   /api/v1/client/workspaces/{workspace}/hiring-interviews/{interview}/request-schedule-change
POST   /api/v1/client/workspaces/{workspace}/hiring-interviews/{interview}/apologize
POST   /api/v1/client/workspaces/{workspace}/hiring-request-items/{item}/decision
POST   /api/v1/client/workspaces/{workspace}/hiring-request-items/{item}/hold
POST   /api/v1/client/workspaces/{workspace}/hiring-request-items/{item}/resume
POST   /api/v1/client/workspaces/{workspace}/hiring-request-items/{item}/cancel
```

تتطلب الكتابة صلاحية `client.hiring-requests.manage`، والقراءة صلاحية `client.hiring-requests.view`.

## Endpoints الأدمن

```text
POST   /api/v1/admin/hiring-requests/items/{item}/offers
POST   /api/v1/admin/hiring-requests/items/{item}/offers/respond-on-behalf
POST   /api/v1/admin/hiring-requests/items/{item}/interview/schedule
POST   /api/v1/admin/hiring-requests/items/{item}/interview/confirm
POST   /api/v1/admin/hiring-requests/items/{item}/interview/start
POST   /api/v1/admin/hiring-requests/items/{item}/interview/complete
POST   /api/v1/admin/hiring-requests/items/{item}/decision-on-behalf
DELETE /api/v1/admin/hiring-requests/items/{item}/decision-on-behalf
POST   /api/v1/admin/hiring-requests/items/{item}/hold
POST   /api/v1/admin/hiring-requests/items/{item}/resume
POST   /api/v1/admin/hiring-requests/items/{item}/cancel
```

لا ينفذ إجراءات التشغيل إلا الأدمن المسؤول عن الطلب. الصلاحيات المستخدمة هي صلاحيات Hiring Requests الحالية دون إضافة صلاحيات مكررة.

## أمثلة الـBody

### Counter أوعرض معدل

```json
{
  "salary_amount": 6500,
  "currency_id": 1,
  "work_type": "full_time",
  "work_mode": "hybrid",
  "work_start_day": "sunday",
  "work_end_day": "thursday",
  "hybrid_work_days": ["thursday"],
  "work_hours": 8,
  "notice_period_days": 30,
  "work_location": "Cairo, Egypt"
}
```

### اقتراح موعد

```json
{
  "starts_at": "2026-11-10T12:00:00+02:00"
}
```

### رد طرف على الموعد

```json
{
  "response": "change_requested",
  "reason": "الموعد غير مناسب، برجاء تحديد موعد آخر."
}
```

سبب الموهبة مطلوب عند `change_requested` أو`apologized`، وسبب العميل اختياري طبقًا للتصميم.

### تأكيد المقابلة يدويًا

```json
{
  "meeting_url": "https://meet.google.com/abc-defg-hij"
}
```

يمكن حذف `meeting_url` عند تفعيل Google Calendar ليتم إنشاء الرابط تلقائيًا.

### قرار بعد المقابلة

```json
{
  "decision": "proceed",
  "reason": null
}
```

### رد الأدمن بالنيابة

```json
{
  "decision": "reject",
  "reason": "تم تسجيل القرار بعد التواصل الخارجي."
}
```

## الإعدادات المؤقتة

حتى توصيل Spatie Laravel Settings، مصدر القيم هو `config/hiring.php`:

```text
offer_response_duration_hours = 48
post_interview_decision_duration_hours = 24
talent_offer_no_response_limit = 2
interview_duration_minutes = 60
```

تغيير القيمة يؤثر على المهل الجديدة فقط.

## الاختبارات

تم التشغيل داخل Docker باستخدام SQLite in-memory:

```text
tests/Feature/HiringRequestFlowTest.php
12 tests, 95 assertions — PASS
```

يشمل الاختبار الكامل: Start Request → مرحلة `offer` → قبول العرض → مرحلة `interview` → موعد وموافقة الطرفين → تأكيد وبدء وإكمال المقابلة → قرار العميل → `accepted`، بالإضافة إلى انتهاء عرضين وتعطيل حساب الموهبة دون غلق العنصر.

كما تم تشغيل كل اختبارات Feature على SQLite in-memory للتأكد من عدم كسر S02 وS04 وS05:

```text
28 tests, 191 assertions — PASS
```

## تطبيق التحديث على السيرفر

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize:clear
```

لا توجد صلاحيات جديدة تتطلب Seeder في S06.

## تحديث Apidog محليًا

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

ثم حدّث المصادر الثلاثة:

```text
http://localhost:8000/docs/talent.openapi
http://localhost:8000/docs/client.openapi
http://localhost:8000/docs/admin.openapi
```

التصدير يثبت تلقائيًا `Accept: application/json` و`X-Authorization: {{api_key}}` وBearer `{{access_token}}`.
