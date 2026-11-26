# S07 — تنفيذ أساس العقود والمدفوعات

## الحالة

تم تنفيذ أساس دفع مستقل عن بوابة الدفع، مع إبقاء التوقيع ودفع التعاقد خارج البورتال كما تقرر. التنفيذ الحالي خاص بشراء الخدمات الرقمية الثلاث:

- `qader_expert_opinion` للعميل.
- `detailed_rejection_feedback` للموهبة المرفوضة.
- `profile_expert_review` للموهبة المقبولة.

## قاعدة البيانات

أضيفت Migration واحدة تنشئ الجداول التالية فقط:

- `payments`: عملية الشراء ومالكها والخدمة والمبلغ والحالة.
- `payment_attempts`: محاولة التواصل مع بوابة الدفع وبيانات Checkout غير الحساسة.
- `payment_webhook_events`: أحداث Webhook المشفرة وحالة معالجتها ومنع تكرار الحدث.

لا تحفظ أرقام بطاقات أوCVV أوبيانات دفع حساسة.

## الـEndpoints

### العميل

```text
POST /api/v1/client/workspaces/{workspace}/talent-marketplace/{talent}/expert-opinion/checkout
GET  /api/v1/client/workspaces/{workspace}/payments/{payment_reference}
```

يتطلب الإنشاء Header ثابتًا لكل محاولة منطقية:

```http
Idempotency-Key: c12d9cc7-1157-4ee4-ae58-bb588809d06d
```

الشراء مملوك لـ`owner_client_id + talent_id`؛ لذلك لا يرتبط بمساحة عمل واحدة، ويمكن لعضو في أي Workspace لنفس المالك عرضه حسب صلاحياته.

### الموهبة

```text
POST /api/v1/talent/applications/{application}/evaluation/checkout
GET  /api/v1/talent/payments/{payment_reference}
```

يحدد النظام الخدمة تلقائيًا من حالة الطلب: `rejected` أو`accepted`. لا يسمح بشراء تقرير طلب في حالة أخرى.

### بوابة الدفع

```text
POST /api/v1/webhooks/payments/{provider}
```

المسار لا يستخدم Token المستخدم أوAPI Key؛ الحماية تتم داخل Adapter الخاص بالبوابة عن طريق التوقيع، ثم مقارنة المبلغ والعملة والمراجع قبل اعتماد الدفع.

## الصلاحيات

لا توجد صلاحيات منفصلة للدفع أوعرض مراجعة الخبير. تستخدم الدورة صلاحية السوق الحالية فقط:

- `client.marketplace.view`

الـOwner يمتلكها تلقائيًا. وإذا منحها لعضو في أي Workspace، يستطيع العضو دخول السوق وبدء الدفع وعرض أي مراجعة اشتراها حساب المالك. الشراء مرتبط بـ`owner_client_id + talent_id` وليس بالـWorkspace أوالمستخدم الذي نفذ الدفع، ولذلك يصبح متاحًا للأعضاء المصرح لهم في كل مساحات العمل التابعة لنفس المالك.

## فتح التقارير

- `evaluation_is_available` لا يسمح بالعرض إلا بعد وجود تقرير ودفع ناجح لنفس `talent_application_id`.
- `expert_opinion_is_available` لا يسمح بالعرض إلا بعد دفع ناجح لنفس مالك حساب العميل والموهبة، وامتلاك العضو صلاحية العرض.
- نجاح الـFrontend وحده لا يفتح التقرير؛ المصدر المعتمد هو Webhook أوReconciliation من البوابة.

## منع التكرار والحماية

- كل Checkout يتطلب UUID في `Idempotency-Key`.
- إعادة نفس المفتاح لنفس الطلب تعيد نفس العملية.
- استخدام نفس المفتاح لطلب مختلف يرجع `409`.
- `gateway_request_id` ثابت وفريد لكل محاولة.
- أحداث Webhook فريدة داخل محاولة الدفع.
- Payload الـWebhook محفوظ مشفرًا.
- لا يمكن لإشعار متأخر إرجاع محاولة نهائية إلى حالة سابقة، بينما يسمح بتأكيد `paid` اللاحق.
- توجد Rate Limit مستقلة لمسارات Webhook.

## الـAdapter الحالي

`FakePaymentGateway` متاح فقط في `local` و`testing` لتجربة الدورة. لا يعمل في Production. عند اختيار Paymob أومويسر أوStripe يضاف Adapter يطبق `PaymentGatewayInterface` دون تغيير Business Flow أوالجداول.

## الإعداد المحلي

```env
PAYMENT_GATEWAY=fake
PAYMENT_FAKE_WEBHOOK_SECRET=change-this-local-secret
PAYMENT_WEBHOOK_RATE_LIMIT=120
```

وفي Production يترك `PAYMENT_GATEWAY` فارغًا حتى إضافة Adapter حقيقي؛ عندها يرجع Checkout رسالة واضحة `503` بدل إنشاء عملية وهمية.

## أوامر Docker

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan migrate
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=AdminRoleSeeder --force
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan optimize:clear
docker compose -f compose.yaml -f compose.local.yaml exec app php artisan payments:reconcile
```

المجدول يشغل Reconciliation تلقائيًا كل خمس دقائق للمحاولات المعلقة. يجب أن يظل Laravel Scheduler والـQueue Worker يعملان في بيئة التشغيل، لأن Webhook يعيد الاستجابة سريعًا ثم يعالج الحدث في Job.

## المتبقي عند اختيار البوابة

- إضافة Adapter البوابة وConfig الأسرار.
- تطبيق إنشاء Checkout والتحقق من توقيع Webhook والاستعلام عن العملية حسب توثيق المزود.
- تجربة Sandbox end-to-end وتثبيت Return URLs التي سيعتمدها الـFrontend.
