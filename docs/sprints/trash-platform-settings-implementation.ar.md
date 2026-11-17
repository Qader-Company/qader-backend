# تنفيذ Trash وPlatform Settings

الحالة: **منفذ — 15 نوفمبر 2026**

## Platform Settings

- ثُبتت مكتبة `spatie/laravel-settings` بالإصدار `3.10.2` في `composer.lock`.
- أضيف جدول `settings` وSettings migrations وTyped Settings classes.
- المجموعات المنفذة: `hiring`، `talent_evaluation`، `trash`، `platform`، `website`، `marketplace`، `paid_services`.
- كل تعديل يسجل القيم القديمة والجديدة والمنفذ في `platform_setting_events`.
- تحديث كل مجموعة يقبل الحقول الجزئية فقط ولا يجبر الواجهة على إرسال المجموعة كاملة.
- قيم Hiring وحدود Talent Interview ومدة Trash مستخدمة فعليًا داخل الـBusiness flows.

### Endpoints الأدمن

```text
GET   /api/v1/admin/platform-settings
PATCH /api/v1/admin/platform-settings/hiring
PATCH /api/v1/admin/platform-settings/talent-evaluation
PATCH /api/v1/admin/platform-settings/trash
PATCH /api/v1/admin/platform-settings/platform
PATCH /api/v1/admin/platform-settings/website
PATCH /api/v1/admin/platform-settings/marketplace
PATCH /api/v1/admin/platform-settings/paid-services
```

الصلاحيات:

```text
admin.platform-settings.view
admin.platform-settings.update
```

## Trash

- الحذف العادي Soft Delete ويخفي العنصر من كل القوائم والـflows المعتادة.
- `remove` يخفي العنصر من Trash فقط عن طريق `removed_from_trash_at`؛ لا يحذف الصف من قاعدة البيانات.
- مدة الظهور الافتراضية 30 يومًا وتقرأ لحظيًا من `trash.retention_days`.
- Scheduler يومي يخفي العناصر المنتهية فقط عندما لا توجد علاقات تشغيلية تمنع الإزالة.
- Restore يعيد حالة تفعيل الحساب الأصلية ويلغي أي Tokens قديمة وقت الحذف.
- إزالة حساب فارغ من Trash تحرر البريد الفريد حتى يمكن التسجيل به مجددًا مع إبقاء السجل القديم في قاعدة البيانات.
- كل حذف واستعادة وإزالة يسجل في `trash_events`.

### Admin Trash

كل نوع له مسارات مستقلة تحت:

```text
GET  /api/v1/admin/trash/{resource}
POST /api/v1/admin/trash/{resource}/{id}/restore
POST /api/v1/admin/trash/{resource}/{id}/remove
```

`{resource}` هنا مسار ثابت مولد لكل من:

```text
job-forms, form-submissions, talents, admins, roles, help-requests,
clients, countries, cities, currencies, languages, industries,
professional-fields, job-titles, skills, tools
```

لا توجد Generic route باسم `{type}`، ولا تشمل Admin Trash الـWorkspaces أوHiring Requests.

الصلاحيات:

```text
admin.trash.view
admin.trash.restore
admin.trash.remove
```

### Client Trash

Workspaces المحذوفة متاحة للمالك من مستوى الحساب:

```text
GET  /api/v1/client/trash/workspaces
POST /api/v1/client/trash/workspaces/{workspace}/restore
POST /api/v1/client/trash/workspaces/{workspace}/remove
```

Team وRoles تظل Scoped بالـWorkspace النشطة:

```text
GET  /api/v1/client/workspaces/{workspace}/trash/team
POST /api/v1/client/workspaces/{workspace}/trash/team/{member}/restore
POST /api/v1/client/workspaces/{workspace}/trash/team/{member}/remove

GET  /api/v1/client/workspaces/{workspace}/trash/roles
POST /api/v1/client/workspaces/{workspace}/trash/roles/{role}/restore
POST /api/v1/client/workspaces/{workspace}/trash/roles/{role}/remove
```

الصلاحيات داخل الـWorkspace:

```text
client.trash.view
client.trash.restore
client.trash.remove
```

## تطبيق قاعدة البيانات والسيدرز

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan migrate --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=AdminRoleSeeder --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
```

في Production يوصى بتفعيل:

```dotenv
SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_MEMO=true
```

ويجب أن يكون `php artisan schedule:run` مشغلًا كل دقيقة من Cron حتى تعمل الإزالة التلقائية.

## التحقق

- نجحت migrations كاملة على SQLite من قاعدة فارغة.
- نجحت اختبارات Trash وSettings: 4 اختبارات و17 assertion.
- نجح Regression المشروع كاملًا: 54 اختبارًا و346 assertion.
