# S07 — توثيق تنفيذ العقود والمدفوعات

تاريخ آخر مراجعة: 26 نوفمبر 2026

المرجع الوظيفي: [S07-contracts-payments-plan.ar.md](./S07-contracts-payments-plan.ar.md)

## 1. ملخص الحالة

يغطي S07 ثلاثة أجزاء مترابطة، وليست موديول الدفع فقط:

1. إعداد أنواع العقود وتثبيت إعداد العقد والعرض داخل طلب التعيين.
2. إنهاء دورة طلب التعيين بعد التوقيع ودفع التعاقد خارج البورتال.
3. دفع الخدمات الرقمية وفتح تقارير التقييم المدفوعة.

الحالة الحالية:

| الجزء | الحالة | الملاحظات |
| --- | --- | --- |
| إدارة أنواع العقود | منفذ | النوعان `managed` و`one_time` وإعداداتهما ورسومهما ومزاياهما وشروطهما |
| تطبيق إعداد العقد على طلب التعيين | منفذ | يحفظ Snapshot مستقلًا عن أي تعديل لاحق في إعداد النوع |
| التوقيع داخل البورتال | غير مطلوب | التوقيع يتم خارجيًا حسب القرار المعتمد |
| دفع التعاقد داخل البورتال | غير مطلوب | يتم خارجيًا بالتنسيق مع العميل |
| إكمال طلب التعيين من الأدمن | مخطط ولم ينفذ بعد | لا يوجد حاليًا Endpoint `hiring-requests/{id}/complete` |
| Hired Talents | مخطط ولم ينفذ بعد | لا توجد Endpoints مستقلة للمواهب المتعاقد معها |
| أساس دفع الخدمات الرقمية | منفذ | مستقل عن مزود الدفع ويستخدم ثلاثة جداول فقط |
| ربط التقارير بالدفع | منفذ | Expert Opinion للعميل وتقييما القبول والرفض للموهبة |
| بوابة دفع Production | غير منفذة | تنتظر اعتماد المزود وبيانات Sandbox |

هذا الملف يوثق ما يعمل فعليًا ويحدد الباقي بوضوح؛ لا يعتبر الأجزاء المخططة منفذة.

## 2. القرارات الوظيفية المعتمدة

- التوقيع الحالي يتم خارج البورتال.
- دفع التعاقد المرتبط بطلب التعيين يتم خارج البورتال.
- لا تحفظ نسخة عقد أوإيصال دفع داخل النظام حاليًا.
- لا توجد حالات مستقلة مثل `contract_fully_executed` أو`waiting_to_start` في الـBackend.
- عناصر طلب التعيين تنتهي بنتيجة كل موهبة، بينما الطلب الأب ينتقل لاحقًا إلى `completed` من الأدمن بعد انتهاء الدورة الخارجية.
- الدفع الإلكتروني الحالي مخصص للخدمات الرقمية الثلاث فقط، وليس لراتب الموهبة أودفع التعاقد.
- لا يوجد Refund أوInvoice أوSubscription أوحفظ لوسائل الدفع في النطاق الحالي.

## 3. إدارة أنواع العقود

### 3.1 الأنواع الأساسية

ينشئ `ContractTypeSeeder` النوعين التاليين دون الكتابة فوق إعداد عدله الأدمن سابقًا:

- `managed`: عقد مُدار.
- `one_time`: عمولة لمرة واحدة.

السيدر يضيف البيانات الأساسية والمزايا والشروط عند غيابها فقط، ويبقي النوع غير مفعل حتى يكتمل إعداده.

### 3.2 البيانات المدارة

يدعم نوع العقد:

- الاسم والوصف بالعربية والإنجليزية.
- حالة التفعيل.
- نوع الرسم والقيمة الثابتة والعملة والنسبة.
- Benefits متعددة ومرتبة.
- Terms متعددة ومرتبة.
- Fee overrides حسب المجال المهني وحجم الشركة.
- أيام وساعات العمل الافتراضية.
- Work type وWork mode.
- أيام Hybrid.
- مكان العمل وLatitude وLongitude.

### 3.3 Endpoints الأدمن

```text
GET   /api/v1/admin/contract-types
GET   /api/v1/admin/contract-types/{contractType}
PUT   /api/v1/admin/contract-types/{contractType}
PATCH /api/v1/admin/contract-types/{contractType}/status
```

الصلاحيات المستخدمة:

```text
admin.contract-types.view
admin.contract-types.update
admin.contract-types.toggle-status
```

لا يسمح بتفعيل النوع قبل اكتمال بيانات الرسم والعمل المطلوبة. الأنواع `onsite` و`hybrid` تتطلب بيانات مكان العمل والإحداثيات.

### 3.4 قاعدة البيانات والملفات

Migration:

```text
database/migrations/2026_11_06_000000_create_contract_type_configuration_tables.php
```

الجداول:

- `contract_types`
- `contract_type_benefits`
- `contract_type_terms`
- `contract_type_fee_overrides`

الموديول:

```text
app/Modules/V1/ContractTypes/
database/seeders/ContractTypeSeeder.php
routes/v1/admin/contract-types.php
```

## 4. ربط العقد بطلب التعيين

### 4.1 مصدر الإعدادات عند إنشاء الطلب

عند إرسال طلب تعيين، يستدعي النظام `ContractConfigurationResolver` لكل Talent، ويتحقق من:

- أن نوع العقد مفعل.
- أن راتب الموهبة المتوقع وعملته موجودان.
- أن الراتب المرسل يقع داخل نطاق راتب الموهبة.
- اكتمال إعداد أيام وساعات ونمط العمل.
- صلاحية ساعات البداية والنهاية وإمكانية حساب عدد ساعات العمل.
- اكتمال الرسم الثابت وعملته؛ لأن التدفق الحالي يعتمد الرسم الثابت فقط.
- وجود Fee override مطابق للمجال المهني وحجم الـWorkspace عند توفره، وإلا يستخدم الرسم الافتراضي.

### 4.2 البيانات المثبتة Snapshot

يحفظ `hiring_request_items.contract_configuration_snapshot` نسخة من:

- نوع العقد واسمه ووصفه.
- Benefits وTerms وقت إنشاء الطلب.
- مصدر الرسم: افتراضي أوOverride.
- قيمة الرسم الثابت وعملته.

ويحفظ العرض المرتبط بالعنصر القيم المحسوبة والمثبتة مثل:

- الراتب وعملته.
- Work type وWork mode.
- أيام العمل وبدايتها ونهايتها.
- `hybrid_work_days`.
- `work_hours` المحسوبة من وقت البداية والنهاية.
- `notice_period_days` من ملف الموهبة.
- مكان العمل والإحداثيات.

تغيير إعداد نوع العقد بعد إنشاء طلب التعيين لا يغير الطلب القديم.

### 4.3 ظهور أنواع العقود للعميل

ترجع أنواع العقود المتاحة ضمن بيانات اختيار المواهب قبل إرسال طلب التعيين. `ClientContractTypeResource` يعيد الاسم حسب `Accept-Language` مع الرسم الثابت والإعدادات الافتراضية اللازمة للعرض.

## 5. دورة التعاقد الخارجية

بعد قبول العرض وإكمال المقابلة وقرار العميل:

```text
قبول الموهبة
→ تنسيق Qader خارجيًا
→ توقيع العقد خارج البورتال
→ دفع التعاقد خارج البورتال
→ تأكيد الأدمن اكتمال طلب التعيين
```

لا توجد في التنفيذ الحالي:

- شاشة توقيع إلكتروني.
- رفع عقد موقع.
- رفع أوتوثيق إيصال دفع التعاقد.
- Contract lifecycle أوContract status مستقلة.
- Action منفصلة لتأكيد التوقيع.
- Action منفصلة لتأكيد دفع التعاقد.

### 5.1 الجزء المتبقي: إكمال طلب التعيين

الـEndpoint المعتمد في الخطة ولم ينفذ بعد:

```text
POST /api/v1/admin/hiring-requests/{hiringRequest}/complete
```

المطلوب عند تنفيذه:

- يستخدم `admin.hiring-requests.handle`.
- ينفذه الأدمن المسؤول عن الطلب.
- يشترط أن يكون الطلب `active`.
- يمنع الإكمال إذا وجد Item في `pending`, `offer`, `interview` أو`on_hold`.
- يبقي العناصر النهائية في `accepted`, `rejected` أو`cancelled` دون إنشاء حالات عقد مشتقة.
- يحدث الطلب الأب إلى `completed` داخل Transaction.
- يسجل Event واحدًا باسم `completed` مع أعداد النتائج النهائية.
- لا يستقبل حقول `signed` أو`paid` ولا يرفع مستندات.

### 5.2 الجزء المتبقي: Hired Talents

الـEndpoints المعتمدة في الخطة ولم تنفذ بعد:

```text
GET /api/v1/client/workspaces/{workspace}/hired-talents
GET /api/v1/client/workspaces/{workspace}/hired-talents/{item}
```

مصدر البيانات سيكون Hiring Request Item دون جدول جديد:

```text
hiring_request.status = completed
AND hiring_request_item.status = accepted
AND hiring_request.workspace_id = current workspace
```

سيكون `{item}` هو معرف عنصر طلب التعيين للحفاظ على ارتباط شروط العرض بعملية التعيين الصحيحة. بيانات الاتصال لا تظهر من هذا المسار إلا بعد اكتمال الطلب.

## 6. الخدمات الرقمية المدفوعة

الخدمات المنفذة:

| الخدمة | المشتري | الهدف | السعر الافتراضي |
| --- | --- | --- | --- |
| `qader_expert_opinion` | عضو Client لديه صلاحية السوق | Talent profile | `250 EGP` |
| `detailed_rejection_feedback` | الموهبة | Talent Application مرفوضة | `100 EGP` |
| `profile_expert_review` | الموهبة | Talent Application مقبولة | `10 USD` |

الدفع يفتح تقريرًا موجودًا بالفعل ولا ينشئ مهمة Review جديدة. شراء Detailed Feedback لا يغير سياسة إعادة التقديم ولا `reapply_at`.

### 6.1 Platform Settings

الأسعار والعملات موجودة في `PaidServicesSettings` ويمكن للأدمن تعديلها عبر إعدادات المنصة:

```text
GET   /api/v1/admin/platform-settings
PATCH /api/v1/admin/platform-settings/paid-services
```

مفاتيح الإعدادات:

```text
qader_expert_opinion_price
qader_expert_opinion_currency
detailed_rejection_feedback_price
detailed_rejection_feedback_currency
profile_expert_review_price
profile_expert_review_currency
```

السعر والعملة يؤخذان من الإعدادات ويحفظان على Payment وقت بدء العملية؛ لا يستقبلهما النظام من الـFrontend.

## 7. هيكل موديول الدفع

### 7.1 قاعدة البيانات

Migration:

```text
database/migrations/2026_11_25_000000_create_payment_tables.php
```

الجداول الثلاثة فقط:

- `payments`: عملية الشراء والمالك والهدف والخدمة والسعر والحالة.
- `payment_attempts`: محاولة بوابة الدفع وبيانات Checkout غير الحساسة.
- `payment_webhook_events`: أحداث Webhook المشفرة وحالة معالجتها ومنع التكرار.

لا تحفظ أرقام بطاقات أوCVV أوAuthorization headers أوأسرار التوقيع.

### 7.2 ملكية الشراء

#### العميل

- شراء Expert Opinion يتبع `owner_client_id + talent_id`.
- الـWorkspace في المسار هي سياق العضوية والصلاحية فقط.
- المستخدم الذي بدأ العملية يحفظ في `payer_user_id` للتتبع.
- الشراء يفتح التقرير لمالك الحساب وكل عضو لديه صلاحية السوق داخل أي Workspace يملكها نفس المالك.

#### الموهبة

- الشراء يتبع حساب الموهبة في `payer_user_id`.
- الهدف هو `talent_application_id` بعينه.
- الطلب المرفوض يختار `detailed_rejection_feedback` تلقائيًا.
- الطلب المقبول يختار `profile_expert_review` تلقائيًا.
- شراء تقرير محاولة لا يفتح تقارير المحاولات الأخرى.

### 7.3 الحالات

حالات Payment والمحاولة الموحدة:

```text
pending
processing
paid
failed
cancelled
expired
```

فتح التقرير يعتمد على `paid` فقط.

## 8. Endpoints الدفع والتقارير

### 8.1 العميل

```text
POST /api/v1/client/workspaces/{workspace}/talent-marketplace/{talent}/expert-opinion/checkout
GET  /api/v1/client/workspaces/{workspace}/payments/{payment_reference}
GET  /api/v1/client/workspaces/{workspace}/talent-marketplace/{talent}/expert-opinion
```

### 8.2 الموهبة

```text
POST /api/v1/talent/applications/{application}/evaluation/checkout
GET  /api/v1/talent/payments/{payment_reference}
GET  /api/v1/talent/profile/evaluation
```

مسار Checkout يرتبط بالـApplication المحددة، بينما مسار عرض التقرير الحالي يعرض تقييم ملف الموهبة وفق الـApplication التي يحلها الـBackend للمستخدم. لا توجد حاليًا Endpoint عرض مستقلة باسم `applications/{application}/evaluation`.

### 8.3 Webhook

```text
POST /api/v1/webhooks/payments/{provider}
```

مسار Webhook لا يستخدم Bearer Token أو`X-Authorization`. الحماية مسؤولية Adapter المزود عن طريق التوقيع الرسمي، ثم مطابقة المرجع والمبلغ والعملة قبل تسجيل النجاح.

### 8.4 منع تكرار الدفع

- لا يرسل الـFrontend `Idempotency-Key`.
- يولد الـBackend `purchase_key` ثابتة من الخدمة والهدف.
- لكل خدمة وهدف Payment واحدة فقط، ويحميها Unique index في قاعدة البيانات.
- تكرار Checkout أثناء محاولة فعالة يعيد Payment ومحاولة الدفع نفسهما.
- بعد `failed`, `cancelled` أو`expired` تنشأ `payment_attempt` جديدة تحت Payment نفسها.
- `gateway_request_id` يمنع تكرار إنشاء محاولة المزود عند Timeout.
- حدث Webhook المكرر يعيد نجاحًا دون تكرار المعالجة.

## 9. الصلاحيات وفتح التقارير

لا توجد صلاحيات Client منفصلة للدفع أورؤية Expert Opinion. تستخدم الدورة:

```text
client.marketplace.view
```

- الـOwner يمتلكها تلقائيًا.
- منحها لعضو يسمح له بتصفح السوق وبدء الدفع ورؤية المراجعات التي اشتراها حساب المالك.
- لا يستطيع العضو فتح Workspace ليس عضوًا فيها حتى لو كان الشراء مشتركًا تحت المالك نفسه.

الحقول المستخدمة في الواجهات:

- `expert_opinion_is_available`: يكون صحيحًا عند وجود التقرير وPayment ناجحة لنفس المالك والموهبة.
- `evaluation_is_available`: يكون صحيحًا عند وجود التقرير وPayment ناجحة لنفس Talent Application.

نجاح Redirect أوSDK في الـFrontend لا يفتح التقرير؛ المصدر المعتمد هو Webhook موثق أوReconciliation من المزود.

## 10. حماية Webhook ومعالجة الحالات

- `PaymentGatewayInterface` يعزل Business Flow عن المزود.
- Adapter المزود يتحقق من التوقيع قبل تعديل قاعدة البيانات.
- Payload الحدث يحفظ مشفرًا وبعد تنقيته.
- Unique على محاولة الدفع ومعرف الحدث يمنع Replay الداخلي.
- المعالجة تستخدم Queue وDatabase locks.
- لا يسمح لحدث متأخر بإرجاع Payment مدفوعة إلى حالة أقدم.
- يسمح بتأكيد `paid` اللاحق إذا وصل بعد حالة مؤقتة أوفشل قابل للمراجعة.
- `payments:reconcile` يراجع المحاولات المعلقة عند فقد Webhook.
- Scheduler يشغل Reconciliation كل خمس دقائق.
- توجد Rate Limit مستقلة للـWebhook.

## 11. الـAdapter الحالي والمتبقي للإنتاج

`FakePaymentGateway` متاح في `local` و`testing` فقط، ويرفض العمل في Production.

عند اختيار Paymob أومويسر أوStripe يضاف Adapter واحد يطبق:

```php
interface PaymentGatewayInterface
{
    public function createCheckout(PaymentAttempt $attempt): CheckoutSessionData;

    public function parseWebhook(Request $request): PaymentWebhookData;

    public function fetchPayment(PaymentAttempt $attempt): GatewayPaymentData;
}
```

لا تحتاج الجداول أوBusiness Flow إلى تغيير عند إضافة المزود. المطلوب للإنتاج:

1. اعتماد المزود.
2. إضافة Adapter وConfig الأسرار.
3. تطبيق إنشاء Checkout والتحقق من توقيع Webhook والاستعلام عن العملية.
4. تثبيت Return URLs مع الـFrontend.
5. تنفيذ Sandbox end-to-end.

## 12. الملفات الأساسية

```text
app/Modules/V1/ContractTypes/
app/Modules/V1/HiringRequests/Application/Services/ContractConfigurationResolver.php
app/Modules/V1/Payments/
app/Console/Commands/ReconcilePendingPaymentsCommand.php
app/Settings/PaidServicesSettings.php
config/payments.php
config/modules.php
routes/v1/admin/contract-types.php
routes/v1/client/payments.php
routes/v1/talent/payments.php
routes/v1/webhooks/payments.php
database/migrations/2026_11_06_000000_create_contract_type_configuration_tables.php
database/migrations/2026_11_25_000000_create_payment_tables.php
database/settings/2026_11_15_000000_create_platform_settings.php
database/seeders/ContractTypeSeeder.php
lang/ar/payments.php
lang/en/payments.php
tests/Feature/PaymentFoundationTest.php
tests/Feature/ClientTalentMarketplaceFlowTest.php
tests/Feature/TalentEvaluationReadTest.php
```

## 13. الاختبارات المنفذة

التغطية الحالية تتحقق من:

- منع تكرار Payment من الـBackend عند إعادة Checkout لنفس الخدمة والهدف.
- إنشاء Attempt جديدة بعد فشل المحاولة السابقة دون إنشاء Payment أخرى.
- التحقق من Webhook وتسجيل الدفع الناجح.
- تجاهل Webhook المكرر.
- مشاركة Expert Opinion بين Workspaces التابعة لنفس المالك.
- منع عرض Expert Opinion قبل الدفع.
- منع عرض تقييم الموهبة قبل الدفع.
- فتح التقرير الصحيح بعد Payment ناجحة.
- استقلال شراء Talent Application عن المحاولات الأخرى.

آخر تشغيل مركز وقت تنفيذ أساس الدفع:

```text
12 tests passed
105 assertions
```

## 14. أوامر التطبيق عبر Docker

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan migrate
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=ContractTypeSeeder --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan payments:reconcile
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan scribe:generate
```

يجب تشغيل Queue Worker وLaravel Scheduler في بيئة التشغيل لمعالجة Webhooks وReconciliation.

إعداد Local/Testing:

```env
PAYMENT_GATEWAY=fake
PAYMENT_FAKE_WEBHOOK_SECRET=change-this-local-secret
PAYMENT_WEBHOOK_RATE_LIMIT=120
```

في Production لا يضبط `PAYMENT_GATEWAY=fake`. قبل إضافة Adapter حقيقي يرجع Checkout استجابة `503` واضحة بدل إنشاء عملية وهمية.

## 15. المتبقي لإغلاق S07 كاملًا

1. تنفيذ Action إكمال طلب التعيين من الأدمن وفق الشروط المعتمدة.
2. تنفيذ قائمة وتفاصيل Hired Talents من الطلبات المكتملة والعناصر المقبولة.
3. إضافة Adapter بوابة الدفع التي ستعتمد للإنتاج وتجربتها على Sandbox.
4. إعادة توليد OpenAPI واستيراده في Apidog بعد تنفيذ الجزأين الأول والثاني واعتماد المزود.

لا يدخل ضمن المتبقي توقيع إلكتروني أوحفظ عقد أوإيصال دفع التعاقد؛ هذه الأجزاء خارج النطاق الحالي بقرار صريح.
