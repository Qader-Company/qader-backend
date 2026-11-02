# S05B — تنفيذ إعدادات أنواع العقود وتبسيط طلب التعيين

الحالة: **تم التنفيذ**  
تاريخ التنفيذ: 2 نوفمبر 2026

## ما تم تنفيذه

- موديول Admin لإعدادات أنواع العقود System Defined.
- النوعان الثابتان: `managed` و`one_time`.
- Basic Information عربي/إنجليزي.
- تفعيل وتعطيل النوع دون حذفه.
- الرسوم: `fixed`, `percentage`, `fixed_percentage`.
- Benefits متعددة ومرتبة.
- Terms متعددة ومرتبة عربي/إنجليزي.
- Fee Overrides حسب Professional Field و`min_employees/max_employees`.
- منع تداخل Employee Ranges الفعالة لنفس المجال ونوع العقد.
- Defaults لأيام وأوقات ونوع ونظام العمل وHybrid Days.
- Work Location مع `work_latitude/work_longitude`.
- حساب `work_hours` من وقت البداية والنهاية وتخزين العدد في العرض.
- Snapshot ثابت لإعدادات العقد على كل Hiring Request Item.
- التحقق أن الراتب داخل Expected Salary Range للموهبة.
- أخذ عملة العرض تلقائيًا من `expected_currency_id`.
- أخذ Notice Period تلقائيًا من بروفايل الموهبة.
- Preview للطلب قبل التأكيد دون حفظ.
- `POST hiring-requests` ينشئ الطلب مباشرة بحالة `pending` ويحذف العناصر المرسلة من Squad ذريًا.
- الإبقاء مؤقتًا على Update Draft وSubmit Draft لدعم المسودات القديمة وعدم كسر ربط قائم.
- عدم تغيير دورة Assign/Reassign/Start Request أوS06.

## Migration

```text
database/migrations/2026_11_06_000000_create_contract_type_configuration_tables.php
```

تنشئ:

- `contract_types`
- `contract_type_benefits`
- `contract_type_terms`
- `contract_type_fee_overrides`

وتضيف:

- `hiring_request_items.contract_type_id`
- `hiring_request_items.contract_configuration_snapshot`
- `hiring_offers.work_latitude`
- `hiring_offers.work_longitude`

يتم Backfill لـ`contract_type_id` من قيمة `contract_type` القديمة، ولا يحذف أي طلب أوعرض موجود.

## Seeder

```text
database/seeders/ContractTypeSeeder.php
```

يستخدم `updateOrCreate` ويحافظ على الإعدادات التي عدلها الأدمن. النوعان يبدأان غير فعالين عند أول Migration حتى يكمل الأدمن الإعدادات ثم يفعلهما.

## Endpoints الأدمن

```text
GET   /api/v1/admin/contract-types
GET   /api/v1/admin/contract-types/{contractType}
PUT   /api/v1/admin/contract-types/{contractType}
PATCH /api/v1/admin/contract-types/{contractType}/status
```

لا توجد Create أوDelete endpoints لأن الأنواع System Defined.

## صلاحيات الأدمن

```text
admin.contract-types.view
admin.contract-types.update
admin.contract-types.toggle-status
```

## Endpoints العميل المعدلة

```text
POST /api/v1/client/workspaces/{workspace}/hiring-requests/preview
POST /api/v1/client/workspaces/{workspace}/hiring-requests
```

الـBody في الاثنين:

```json
{
  "items": [
    {
      "talent_id": 12,
      "contract_type": "managed",
      "interview_required": true,
      "salary_amount": 25000
    }
  ]
}
```

العميل لا يرسل العملة أوشروط العمل أوالموقع أوNotice Period.

## قواعد Work Location

- في `onsite` و`hybrid`: العنوان وLatitude وLongitude مطلوبون معًا.
- في `remote`: الموقع اختياري.
- Latitude من `-90` إلى `90`.
- Longitude من `-180` إلى `180`.
- القيم تنسخ إلى العرض ولا تتغير بعد تعديل إعدادات العقد.

## اختيار Fee Override

الـWorkspace تحفظ حاليًا `size_band` وليست Employee Count دقيقة. لذلك يطبق Override فقط إذا كان نطاقه يغطي Size Band كاملة. مثال:

```text
Workspace size_band: 11-50
Override: 11-50     => يطبق
Override: 11-30     => لا يطبق؛ يستخدم Default Fee
```

`500+` تستخدم Default Fee حاليًا لأن الحد الأعلى غير معروف. يمكن تحسين ذلك لاحقًا بإضافة `employee_count` للـWorkspace.

## التوافق مع البيانات القديمة

- بقي `hiring_request_items.contract_type` دون حذف.
- API ما زالت ترجع `contract_type` كنص.
- المسودات القديمة لا تحذف.
- Endpoints تعديل وإرسال Draft القديمة بقيت مؤقتًا.
- الطلبات الجديدة تستخدم Confirm المباشر ولا تنشئ Draft.

## الاختبارات

تم تشغيل الاختبارات داخل Docker باستخدام SQLite `:memory:`:

```text
ContractTypeConfigurationTest: 2 tests, 14 assertions — PASS
HiringRequestFlowTest: 10 tests, 81 assertions — PASS
Feature suite: 30 tests, 197 assertions — PASS
```

## تطبيق التحديث على السيرفر

بعد رفع الكود:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=ContractTypeSeeder --force
php artisan db:seed --class=AdminRoleSeeder --force
php artisan optimize:clear
```

بعد ذلك يفتح الأدمن كل نوع عقد، يكمل إعداداته، ثم يفعله. قبل التفعيل سترفض API إنشاء طلب جديد بهذا النوع برسالة واضحة.

## تحديث Apidog محليًا

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

ثم Import/Update من:

```text
http://localhost:8000/docs/admin.openapi
http://localhost:8000/docs/client.openapi
```
