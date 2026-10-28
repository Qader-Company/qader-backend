# S04 — تنفيذ اكتشاف المواهب وبناء الفريق

تاريخ التنفيذ: 27 أكتوبر 2026.

## النطاق المنفذ

- عرض المواهب المقبولة فقط داخل Talent Marketplace الخاصة بالـWorkspace.
- البحث المهني والفلاتر والترتيب والـpagination من الـBackend.
- صفحة تفاصيل مهنية آمنة دون كشف هوية الموهبة أوبيانات الاتصال أوCV أوالصورة الشخصية.
- حفظ أكثر من موهبة في قائمة مشتركة داخل الـWorkspace، مع الإضافة والإزالة ومنع التكرار.
- حساب `is_selected`, `selected_talents_count` و`available_actions` بالنسبة إلى الـWorkspace وصلاحيات العضو.
- إيقاف S04 عند حفظ الفريق فقط؛ لا ينشأ Hiring Request أوInterview أوOffer أوContract.

الخطة والقرارات المعتمدة موجودة في [S04 Talent Discovery Plan](S04-talent-discovery-plan.ar.md).

## شروط ظهور الموهبة

تظهر الموهبة فقط عند تحقق الشروط الثلاثة معًا:

1. يوجد لها `talent_applications.status = accepted`.
2. حساب المستخدم فعال.
3. لا يوجد لها سجل Blacklist فعال، أي `removed_at = null`.

لا يوجد جدول Marketplace profile أوعملية نشر مستقلة. بيانات القائمة والتفاصيل تقرأ من `talents` وعلاقات الملف المهني الحالية، لذلك ينعكس تعديل البيانات المهنية مباشرة.

## قاعدة البيانات

تمت إضافة `talents.talent_code` بصيغة `QDR-XXXXXXXX`:

- فريد وثابت ولا يعتمد على Talent ID أوApplication ID.
- يولد تلقائيًا عند إنشاء Talent جديدة.
- Migration التحديث تولد قيمة للمواهب الموجودة قبل جعل العمود غير قابل لـ`null`.

Migration السبرنت:

```text
database/migrations/2026_11_03_000000_create_s04_talent_discovery_schema.php
```

تنشئ `workspace_talent_selections`:

| العمود | الغرض |
| --- | --- |
| `workspace_id` | الـWorkspace المالكة للاختيار |
| `talent_id` | الموهبة المختارة |
| `added_by_member_id` | عضو الـWorkspace الذي أضافها |
| timestamps | وقت الإضافة والتحديث |

المفتاح الأساسي مركب من `workspace_id` و`talent_id`، ولذلك لا يمكن تكرار الموهبة داخل نفس الـWorkspace. لا يحتوي الجدول حالة توظيف أوعرض أوعقد.

تمت محاذاة [مقترح السكيما](../database-schema-proposal.dbml) مع القرار النهائي: لا توجد `talent_marketplace_profiles` أوAvailability إضافية، وتستخدم Industries وProfessional Fields كعلاقات متعددة.

## الصلاحيات

```text
client.marketplace.view
client.talent-selections.view
client.talent-selections.manage
```

- Owner داخل الـWorkspace يملكها ضمن سلوك تجاوز الصلاحيات الحالي.
- العضو العادي يحتاج الصلاحية في أحد أدواره داخل الـWorkspace.
- عدم وجود عضوية فعالة يرجع `404` حتى لا يكشف وجود Workspace أخرى.
- وجود العضوية مع نقص الصلاحية يرجع `403`.

بعد تطبيق الكود يجب مزامنة الصلاحيات الجديدة باستخدام `PermissionSeeder`.

## Headers المشتركة

كل endpoints التالية تحتاج:

```text
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{access_token}}
```

## Endpoints المنفذة

### Talent Marketplace

| Method | Endpoint | الوظيفة |
| --- | --- | --- |
| GET | `/api/v1/client/workspaces/{workspace}/talent-marketplace/filters` | كل خيارات الفلاتر والترتيب |
| GET | `/api/v1/client/workspaces/{workspace}/talent-marketplace` | البحث والقائمة والفلترة والترتيب |
| GET | `/api/v1/client/workspaces/{workspace}/talent-marketplace/{talent}` | التفاصيل المهنية الآمنة |

Query parameters للقائمة:

```text
search
job_title_id
seniority_level[]
years_of_experience_min
salary_min
salary_max
currency_id
skill_ids[]
tool_ids[]
work_types[]
work_modes[]
notice_period_days_max
country_id
city_id
language_id
language_proficiency_level
sort
per_page
page
```

مثال:

```text
GET /api/v1/client/workspaces/1/talent-marketplace?skill_ids[]=1&work_modes[]=remote&seniority_level[]=senior&sort=highest_match&page=1
```

أي فلترة أوترتيب بالراتب يتطلب `currency_id`. لا يتم تحويل العملات في S04، وتتم المقارنة فقط مع المواهب التي تستخدم العملة نفسها.

### الفريق المحفوظ

| Method | Endpoint | الوظيفة |
| --- | --- | --- |
| GET | `/api/v1/client/workspaces/{workspace}/talent-selections` | المواهب المختارة داخل الـWorkspace |
| POST | `/api/v1/client/workspaces/{workspace}/talent-selections` | إضافة موهبة |
| DELETE | `/api/v1/client/workspaces/{workspace}/talent-selections/{talent}` | إزالة موهبة |

Body الإضافة JSON:

```json
{
  "talent_id": 15
}
```

إضافة نفس الموهبة مرتين ترجع `422`. إزالة موهبة غير موجودة في اختيارات الـWorkspace ترجع `404`.

## حماية هوية الموهبة

Resources الخاصة بالـMarketplace لا تحمل `UserResource` ولا ترجع:

- الاسم الحقيقي.
- البريد.
- الهاتف أوWhatsApp.
- الصورة الشخصية.
- CV.
- روابط LinkedIn أوGitHub الشخصية المباشرة.

الواجهة تعرض Avatar افتراضية ثابتة. المعرّف المرئي هو `talent_code`. التفاصيل المهنية المسموحة تشمل المجال والمسمى والخبرة والمهارات والأدوات واللغات والمشروعات والراتب الاسترشادي.

`expert_opinion` ترجع `null` حاليًا إلى أن ينفذ الدفع وإثبات صلاحية الوصول.

## الترتيب Highest Match

لا يوجد Recommendation Engine أوScore مخزن. الترتيب البسيط الحالي يعتمد على:

1. تطابق Professional Field مع اختيارات الـWorkspace.
2. عدد Industries المشتركة مع اختيارات الـWorkspace.
3. حداثة قبول Talent.

باقي الفلاتر تطبق قبل الترتيب.

## تطبيق التحديث على السيرفر

بعد نشر الكود، ومن داخل مشروع Docker الصحيح:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan migrate --force
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=PermissionSeeder --force
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

استخدم اسم مشروع Docker المناسب للبيئة على السيرفر بدل `qader-backend-local` عند اختلافه.

## تحديث Apidog

حدّث مصدر Client من:

```text
http://localhost:8000/docs/client.openapi
```

المسارات الجديدة منظمة في مجلدين:

```text
client/talent-marketplace
client/talent-selections
```

ملف OpenAPI المولد يحتوي `Accept: application/json` و`X-Authorization: {{api_key}}` و`Authorization: Bearer {{access_token}}`، مع path/query parameters وJSON body الخاص بالإضافة.

## التحقق المنفذ

- نجحت migrations من الصفر على SQLite `:memory:` داخل Docker.
- نجح `ClientTalentMarketplaceFlowTest`: أربع حالات و40 assertion.
- نجح معه `ClientWorkspaceFlowTest` القديم دون كسر: الإجمالي خمس حالات و49 assertion.
- تم التحقق من استبعاد Talent المرفوضة وغير النشطة والمحظورة.
- تم التحقق من عدم تسريب الاسم أوالبريد أوالهاتف أوCV.
- تم التحقق من الإضافة ومنع التكرار والقائمة والإزالة وعزل الـWorkspace.
- نجح `scribe:generate`، وتم فحص Client OpenAPI داخل volume الكونتينر والتأكد من الـheaders والـparameters والـbody والمجلدات.

تم كذلك تثبيت إعداد PHPUnit على SQLite باستخدام `force="true"` حتى لا تتغلب متغيرات Docker الخاصة بـMySQL على قاعدة الاختبار المعزولة.
