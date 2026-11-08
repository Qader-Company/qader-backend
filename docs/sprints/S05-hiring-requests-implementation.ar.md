# S05 — تنفيذ طلبات التوظيف

تم تنفيذ Backend الخاص بإنشاء طلب التوظيف من المواهب المحفوظة داخل Workspace، ثم وصوله للأدمن وتعيينه وبدء الطلب وإرسال العرض الأول لكل موهبة.

## ما تم تنفيذه

- حفظ وتعديل Hiring Request كمسودة.
- كل Talent لها Item وعرض شهري مستقل بعملته وشروط العمل.
- Submit ذري للطلب وعناصره.
- حذف المواهب المستخدمة من `workspace_talent_selections` بعد نجاح Submit فقط.
- منع وجود طلب `pending` أو`active` آخر لنفس Talent داخل Workspace نفسها.
- قائمة وتفاصيل الطلبات للعميل داخل Workspace مع عزل كامل بين المساحات.
- قائمة All/My Requests وتفاصيل الطلب للأدمن.
- Assign وReassign للأدمن المسؤول.
- Start Request مع التعيين التلقائي للأدمن الحالي إذا لم يكن الطلب معينًا.
- تحويل العناصر إلى `awaiting_talent_response` وإرسال عروضها.
- تثبيت `sent_at` و`response_due_at` لكل عرض عند الإرسال.
- مهلة الرد الافتراضية 48 ساعة من `config/hiring.php` حتى تنفيذ Platform Settings.
- إرجاع `is_overdue` كقيمة محسوبة دون إضافة حالة عرض جديدة.
- إرجاع Notice Period من بيانات Talent دون تكرارها في `hiring_offers`.
- Audit events للإنشاء والتعديل والإرسال والتعيين وإعادة التعيين والبدء وإرسال العرض.
- `available_actions` حسب الحالة والصلاحية والمسؤول الحالي.
- `available_actions` موحدة كـArray من أسماء الإجراءات مثل `["assign", "start"]`، بنفس نمط الموديولات السابقة.

## الجداول

- `hiring_requests`
- `hiring_request_items`
- `hiring_offers`
- `hiring_request_events`

المigration الجديدة:

```text
database/migrations/2026_11_04_000000_create_s05_hiring_request_tables.php
```

## الصلاحيات

### Client Workspace

```text
client.hiring-requests.view
client.hiring-requests.manage
```

دور Workspace `owner` يتجاوز الصلاحيات التفصيلية داخل مساحته فقط.

### Admin

```text
admin.hiring-requests.view
admin.hiring-requests.assign
admin.hiring-requests.handle
admin.hiring-requests.respond-on-behalf
```

## Endpoints العميل

```text
GET    /api/v1/client/workspaces/{workspace}/hiring-requests/options
GET    /api/v1/client/workspaces/{workspace}/hiring-requests
POST   /api/v1/client/workspaces/{workspace}/hiring-requests
GET    /api/v1/client/workspaces/{workspace}/hiring-requests/{hiringRequest}
PUT    /api/v1/client/workspaces/{workspace}/hiring-requests/{hiringRequest}
POST   /api/v1/client/workspaces/{workspace}/hiring-requests/{hiringRequest}/submit
```

## Endpoints الأدمن

```text
GET  /api/v1/admin/hiring-requests/options
GET  /api/v1/admin/hiring-requests
GET  /api/v1/admin/hiring-requests/assignees
GET  /api/v1/admin/hiring-requests/{hiringRequest}
POST /api/v1/admin/hiring-requests/{hiringRequest}/assign
POST /api/v1/admin/hiring-requests/{hiringRequest}/reassign
POST /api/v1/admin/hiring-requests/{hiringRequest}/start
```

## مثال إنشاء أوتعديل Draft

```json
{
  "items": [
    {
      "talent_id": 12,
      "contract_type": "managed",
      "interview_required": true,
      "offer": {
        "salary_amount": 6500,
        "currency_id": 1,
        "work_type": "full_time",
        "work_mode": "hybrid",
        "work_start_day": "sunday",
        "work_end_day": "thursday",
        "hybrid_work_days": ["thursday"],
        "work_hours": 8
      }
    }
  ]
}
```

`hybrid_work_days` تحتوي أيام الـRemote فقط، وباقي الأيام الواقعة بين `work_start_day` و`work_end_day` تكون On-site. لا ترسل في `remote` أو`onsite`.

## مثال Assign أوReassign

```json
{
  "admin_id": 2
}
```

## الفلو المنفذ

1. العميل ينشئ Draft من المواهب الموجودة في Squad الخاصة بالـWorkspace.
2. يمكنه استبدال محتوى المسودة كاملًا من PUT طالما حالتها `draft`.
3. Submit يعيد التحقق من الاختيار والأهلية وعدم وجود طلب فعال مكرر.
4. يتحول الطلب والعناصر إلى `pending` وتحذف الاختيارات المستخدمة ذريًا.
5. الأدمن يعين مسؤولًا أوينفذ المسؤول الحالي Start Request.
6. Start يحول الطلب إلى `active` والعناصر إلى `awaiting_talent_response`.
7. يثبت لكل عرض `sent_at` وDeadline بعد 48 ساعة حاليًا.
8. رد Talent والتفاوض والمقابلات تكمل في S06.

## الخصوصية

- Client API ترجع `talent_code` والمسمى فقط، ولا ترجع الاسم أوالبريد أوبيانات الاتصال.
- محاولة عضو الوصول إلى طلب داخل Workspace أخرى ترجع `404`.
- Admin API فقط ترجع بيانات التشغيل والـactivity اللازمة.

## الاختبارات

تم تشغيل مجموعة اختبارات المشروع كاملة داخل Docker مع SQLite `:memory:` صريحة.

النتيجة:

```text
25 tests, 142 assertions — OK
```

## تطبيق التحديث على السيرفر

بعد رفع الكود:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=AdminRoleSeeder --force
php artisan optimize:clear
```

السيدر يزامن الصلاحيات الجديدة ويحافظ على البيانات والأدوار المخصصة الموجودة.

## تحديث Apidog محليًا

داخل Docker Compose project المحلي:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

ثم تحديث المصدرين:

```text
http://localhost:8000/docs/client.openapi
http://localhost:8000/docs/admin.openapi
```

أمثلة الـDraft وAssign ثابتة في التوثيق، وتضاف هيدرز `Accept: application/json` و`X-Authorization: {{api_key}}` وBearer `{{access_token}}` تلقائيًا.
