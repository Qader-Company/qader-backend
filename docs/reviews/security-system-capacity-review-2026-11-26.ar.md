# مراجعة الأمان وتصميم النظام والقدرة الاستيعابية

تاريخ المراجعة: 2026-11-26  
النطاق: Backend API في مستودع Qader، وبيئة Docker المحلية، وإعدادات النشر المعلنة لبيئة الاختبار.  
الحالة: مراجعة ساكنة أولية. لم يتم تنفيذ فحص هجومي أو Load Test على `test.api.qadereg.com`.

## 1. الملخص التنفيذي

المشروع منظم إلى Modules واضحة، ويفصل بين بوابات Admin وClient وTalent وWebsite، ويستخدم Sanctum abilities وصلاحيات Admin وصلاحيات Workspace. توجد أيضًا معاملات وقفل سجلات في مسارات حساسة مثل تدوير التوكنات ومعالجة Webhooks.

تم إغلاق المخاطر الفورية الخاصة بعزل الاختبارات والاعتماديات، لكن النسخة لا تعد جاهزة للإنتاج قبل إكمال اختبارات الصلاحيات والتحقق التشغيلي من بيئة النشر:

1. `composer.lock` حدث وأصبح `composer audit` بلا تنبيهات معروفة.
2. PHPUnit أصبح معزولًا على SQLite داخل خدمة Docker مستقلة ومحميًا بـSafety Guard.
3. أوامر الصيانة وOTP الثابت محصوران في `local/testing`، وملفات Compose تفرض `production/testing` و`APP_DEBUG=false` بدل الاعتماد على ملف `.env` وحده.

الأولوية التالية: اختبارات الصلاحيات بين الحسابات وWorkspaces، ثم معالجة SVG ورسائل 404 وسياسة السجلات، ثم الاختبارات الديناميكية والأداء.

## 2. صورة المعمارية الحالية

### 2.1 المكونات

- Laravel 13 / PHP 8.3.
- MySQL للبيانات الأساسية.
- Sanctum لتوكنات Access وRefresh وVerification وReset Password.
- Spatie Permission لصلاحيات Admin، ونظام Workspace Roles/Permissions مخصص للعملاء.
- Queue وScheduler للرسائل والإشعارات وتزامن حالات المقابلات والعروض والدفع.
- Spatie Media Library للملفات العامة والخاصة.
- Spatie Settings لإعدادات المنصة.
- تكاملات خارجية: Firebase، Google، Gemini، وبوابات الدفع عبر Adapter.

### 2.2 حدود الثقة

1. Public Website: التسجيل، الوظائف العامة، طلبات دعم التوظيف، ومحتوى الموقع.
2. Talent Portal: الملف الشخصي، الطلب، المقابلات، العروض، التقييم المدفوع، والإشعارات.
3. Client Portal: مساحات العمل، الأعضاء، الصلاحيات، Marketplace، طلبات التعيين، والدفع.
4. Admin Portal: الإدارة العامة، مراجعة المواهب، العملاء، الطلبات، الإعدادات، والتراش.
5. External Providers: Firebase وGoogle وGemini وبوابات الدفع.
6. Operations: أوامر Artisan، Queue Worker، Scheduler، التخزين، والمفاتيح السرية.

### 2.3 البيانات الحساسة

- كلمات المرور والتوكنات وOTP ومفاتيح API وService Accounts.
- بيانات الهوية والتواصل والسيرة الذاتية والبطاقة الضريبية.
- تقييمات المواهب وتقارير الخبراء وقرارات الرفض والبلاك ليست.
- الرواتب والعروض وطلبات التعيين والمقابلات.
- سجلات الدفع وPayloads الـWebhook.
- عضويات وصلاحيات مساحات العمل.

## 3. النتائج المؤكدة

### SEC-01 — مغلق للإنتاج — أوامر الصيانة متاحة بمفتاح API عام

الدليل:

- `routes/api.php`
- `app/Http/Controllers/MaintenanceCommandController.php`

المسار `/api/maintenance/{command}` يعتمد على `api.key` فقط، ويسمح بـ:

- `migrate-fresh`
- `rollback`
- `seed`
- `create-api-key`
- تشغيل Queue وStorage Link وأوامر أخرى.

القرار المعتمد: تسجيل المسار في بيئتي `local` و`testing` فقط، وعدم تسجيله أصلًا في `production`. لا تتطلب أوامر الصيانة تسجيل دخول لأنها مؤقتة وستحذف لاحقًا، لكنها تظل محمية بمفتاح API.

الأثر المتبقي: بيئة `testing` العامة تظل قابلة للحذف أوالتغيير عند تسريب مفتاحها. هذا خطر مقبول مؤقتًا حسب القرار الحالي، بشرط ألا تحتوي بيانات إنتاجية أوحقيقية وألا تشارك مفاتيح Production.

المعالجة المطلوبة:

1. تحقق حاليًا: لا يسجل Route إلا عندما تكون البيئة `local` أو`testing`.
2. قبل تحويل أي خادم إلى `production` يجب تشغيل `optimize:clear` ثم إعادة بناء Cache والتأكد من غياب Route من `route:list`.
3. عند انتهاء الحاجة، تحذف واجهة HTTP ويستبدل بها CI/CD أوcPanel Task محمي.

### SEC-02 — مغلق للإنتاج — OTP ثابت

الدليل: `app/Modules/V1/Authentication/Domain/Services/OtpService.php`.

القرار المعتمد: `123456` ثابت فقط في `local` و`testing`. في أي بيئة أخرى يولد النظام رقمًا عشوائيًا من ست خانات باستخدام `random_int()`.

الأثر المتبقي: أي حساب على بيئة `testing` يمكن توقع OTP الخاص به. لذلك يجب أن تكون بياناتها وهمية، وألا تشارك قاعدة البيانات أوالمفاتيح أوالتوكنات مع Production.

المعالجة المطلوبة:

1. تحقق حاليًا: Production وما يماثلها تستخدم كودًا عشوائيًا.
2. تحسين لاحق: تخزين Hash للـOTP بدل القيمة الصريحة.
3. الإبقاء على الصلاحية القصيرة، والاستخدام مرة واحدة، وحد المحاولات لكل بريد وغرض وIP.

### SEC-03 — مغلق حاليًا — اعتماديات كان بها تنبيهات أمنية

تم تشغيل:

```bash
composer audit --locked --no-interaction
```

النتيجة قبل المعالجة: 26 Advisory تؤثر في 9 حزم. كانت النسخ المهمة:

- `laravel/framework v13.24.0`
- `league/commonmark 2.9.0`
- `league/flysystem 3.35.2`
- Symfony Components `v7.4.15`

من النتائج تنبيه Critical في Laravel وعدة High في CommonMark وSymfony، إضافة إلى تنبيهات حديثة بتاريخ 2026-11-25.

المعالجة المنفذة:

1. حدث Laravel من `13.24.0` إلى `13.44.1`.
2. حدث Symfony Components المتأثرة إلى `7.4.24`، وCommonMark إلى `2.10.9`، وFlysystem إلى `3.36.0`.
3. أعيد تشغيل `composer audit --locked` وكانت النتيجة: `No security vulnerability advisories found`.
4. نجحت حزمة الاختبارات كاملة بعد التحديث: **95 اختبارًا و615 assertions**.

المتبقي التشغيلي: إضافة `composer audit --locked` كخطوة مانعة في CI عند تجهيز Pipeline.

### SEC-04 — Critical Operational Safety — PHPUnit غير معزول عن قاعدة التطوير

المعلن في `phpunit.xml` هو SQLite in-memory، لكن Docker يشارك `bootstrap_cache`، وكان Config Cache يحتوي إعداد MySQL. نتيجة ذلك اتصل PHPUnit بقاعدة `qader` المحلية ونفذ `RefreshDatabase` عليها.

الأثر الذي حدث أثناء المراجعة:

- أول `DROP TABLE` مؤكد في MySQL binlog: `2026-11-26 13:50:51 +02:00`.
- بعد إيقاف العمليات: 56 جدولًا، 26 migration، وعدد المستخدمين صفر.
- لم يحدث أي تغيير على `test.api.qadereg.com`.
- MySQL Binary Logs متاحة، لذلك Point-in-Time Recovery إلى ما قبل 13:50:51 محتمل.

المعالجة المطلوبة قبل تشغيل أي Test جديد:

1. قاعدة اختبار مستقلة فعليًا مثل `qader_test` أوSQLite داخل container.
2. عدم مشاركة `bootstrap/cache` بين App وTest، أوتشغيل `php artisan config:clear --env=testing` داخل بيئة اختبار منفصلة.
3. إضافة Guard في Test bootstrap يوقف الاختبارات إذا لم يكن اسم قاعدة البيانات مميزًا بوضوح للاختبار.
4. عدم تشغيل `RefreshDatabase` إطلاقًا على قاعدة يمكن أن تحتوي بيانات بشرية.
5. إضافة Backup تلقائي قبل أي Migration أوTest بيئي.

حالة المعالجة الحالية:

- أضيفت مسارات Cache مستقلة لـPHPUnit في `phpunit.xml` حتى لا يقرأ Config/Routes Cache الخاص بتطبيق Docker.
- أضيف Safety Guard إلى `tests/TestCase.php` قبل تشغيل Traits أوMigrations.
- يوقف الـGuard الاختبارات فورًا إلا إذا كانت البيئة `testing` والاتصال `sqlite` وقاعدة البيانات `:memory:`.
- أضيفت خدمة Docker باسم `test` بمتغيرات وStorage وBootstrap Cache مستقلة عن خدمات التطبيق.
- تعيد خدمة الاختبار بناء Package Discovery داخل Cache الاختبار المعزولة قبل PHPUnit، حتى تحمل Service Providers وSettings migrations دون الاعتماد على Cache التطبيق.
- أمر الاختبار المعتمد محليًا:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml run --rm test
```

- تم تشغيل الحزمة كاملة داخل الخدمة المعزولة بنجاح: **93 اختبارًا و610 assertions**.
- بعد إضافة اختبارات بيئية مباشرة أصبح الإجمالي المستهدف **95 اختبارًا و615 assertions**؛ اختبارات OTP ومسار الصيانة الجديدة نجحت منفردة.
- صحح اختبار الإشعارات ليطابق العقد الفعلي: إشعارات Talent وAdmin عامة لكل مستخدم، بينما إشعارات Client مرتبطة بعضوية Workspace ومسارها يحتوي معرفها.
- لم تعد الاختبارات تقرأ أوتكتب قاعدة MySQL المحلية؛ الـSafety Guard يوقفها قبل migrations إذا لم تكن `testing + sqlite + :memory:`.

### SEC-05 — Medium — SVG عام مرفوع من Admin يمكن عرضه من نفس Origin

طلبات Marketing Partner تسمح بـSVG، والملف يحفظ على Public disk ويعرض من `/storage/*`.

الأثر: SVG غير المنظف قد يحتوي محتوى نشطًا. خطورته تعتمد على طريقة عرضه في المتصفح والـFrontend، لكنه سطح XSS غير ضروري.

المعالجة المطلوبة: منع SVG أوتنظيفه بمكتبة موثوقة، ويفضل تحويله إلى PNG/WebP، مع `Content-Disposition` وCSP مناسبة عند الحاجة.

### SEC-06 — Low/Medium — رسائل 404 تكشف أسماء Models والمعرفات

معالج `ModelNotFoundException` يعيد رسالة الاستثناء نفسها. ظهر عمليًا نص مثل:

`No query results for model [...] 7`

المعالجة المطلوبة: رسالة 404 عامة للعميل، مع الاحتفاظ بالتفاصيل في Log داخلي فقط وإضافة Correlation ID.

### SEC-07 — Medium — مفتاح API عام يستخدم كحاجز أمني واسع ويسجل كل الطلبات

`CheckApiKey` يسجل كل وصول صحيح في قاعدة البيانات مع IP وFull URL.

المخاطر:

- إذا كان المفتاح داخل تطبيق Frontend أوMobile فهو ليس Secret فعليًا.
- جدول Access Events قد ينمو بسرعة ويضغط MySQL.
- `fullUrl()` قد يسجل Query Parameters حساسة مستقبلًا.

المعالجة المطلوبة:

1. اعتبار مفتاح التطبيق Identifier/Quota key فقط، وليس بديلًا للمصادقة والصلاحيات.
2. عدم استخدامه لحماية Maintenance.
3. وضع Retention/Aggregation للسجلات وعدم تسجيل قيم حساسة.
4. فصل مفاتيح Website/Mobile/Operations مع إمكانية التدوير والإبطال.

### SEC-08 — مكتمل في ملفات النشر — تثبيت البيئة ووضع Debug

المعالجة المنفذة:

- `compose.production.yaml` يفرض `APP_ENV=production` و`APP_DEBUG=false` على App وQueue وScheduler.
- `compose.testing.yaml` يفرض `APP_ENV=testing` و`APP_DEBUG=false` على الخدمات نفسها.
- أضيف `CORS_ALLOWED_ORIGINS` إلى مثال Docker بدل الاعتماد الصامت على localhost.
- أضيف المسار المتوقع لملف Firebase الخاص إلى مثال Docker؛ الملف الفعلي داخل `storage/app/private` ومغطى بـ`.gitignore`.
- تم التحقق من صحة ملفي Compose.
- تم تشغيل التطبيق فعليًا بـ`APP_ENV=production` وكانت نتيجة البحث عن Maintenance routes فارغة `[]`.
- تم تشغيله بـ`APP_ENV=testing` وظهر Route الصيانة محميًا بـ`CheckApiKey` كما هو معتمد.
- Scribe يضيف مسارات التوثيق في `local` فقط وفق الإعداد الحالي.

المتبقي عند النشر: ضبط Origin الفعلي وعدم ترك قيمة المثال، وتشغيل `optimize:clear` قبل بناء Cache جديد.

## 4. نقاط جيدة موجودة بالفعل

- فصل Portal abilities داخل Sanctum.
- Access/Refresh token families مع تدوير وإبطال الأسرة وقفل السجل أثناء refresh.
- Workspace access يعيد 404 لغير الأعضاء لتقليل تسريب الوجود، ثم يتحقق من الصلاحية.
- التحقق من ملكية الموارد في عدد من المسارات المتداخلة، مثل Hiring Requests وInterviews وJob Submissions.
- ملفات CV والبطاقات الضريبية تحفظ على Private disk وتقدم من Endpoints محمية.
- Webhook الدفع يتحقق من HMAC في Fake Gateway، ويتحقق من المبلغ والعملة، ويستخدم Event uniqueness وDB locks.
- Rate limits موجودة للتسجيل والدخول وOTP والـCV parser وJob Submissions وPayment Webhooks.
- `APP_DEBUG=false` موجود في أمثلة Docker/الإنتاج.
- Service account JSON غير متتبع في Git حسب الفحص الحالي.

## 5. اختبارات أمنية مطلوبة بعد الاحتواء

### P0 — Authorization Matrix / BOLA

إنشاء حسابين على الأقل لكل Portal، ومساحتي عمل، ثم محاولة تبديل كل IDs في:

- Workspace، Members، Roles، Trash.
- Hiring Requests، Items، Offers، Interviews.
- Marketplace، Selections، Expert Opinion، Payments.
- Talent Application، Evaluation، CV، Interview slots.
- Admin nested resources مثل Clients/Workspaces وJob Forms/Submissions.

النتيجة الصحيحة دائمًا: 404 أو403 بدون تسريب بيانات، وعدم تنفيذ أي Mutation.

### P0 — Authentication

- OTP replay/brute force/expiry وربط الغرض والبوابة والبريد.
- Refresh token reuse بعد rotation.
- Tokens بعد logout/reset/suspend/soft delete.
- خلط Talent token مع Client/Admin routes.
- Enumeration بالبريد واختلاف زمن/رسالة الاستجابة.

### P1 — Business Logic

- سباقات قبول/رفض/Counter offer.
- حجز المقابلات المتداخل وإعادة الجدولة.
- تكرار Checkout وWebhook وترتيب أحداث Webhook العكسي.
- شراء نفس الخدمة من مستخدمين في Workspaces مختلفة لنفس Owner.
- إعادة استخدام Job Submission idempotency key بمحتوى مختلف.

### P1 — Uploads وIntegrations

- MIME spoofing وملفات كبيرة وZIP bombs ووثائق تالفة.
- SVG active content.
- التأكد من عدم إظهار CV/Tax Card عبر Public Storage.
- Timeout/retry/failure handling لـGemini وGoogle وFirebase.
- عدم تسجيل Service credentials أوTokens أوCV content في Logs.

### P1 — Security Configuration

- TLS وHSTS وSecurity headers وCORS الفعلي.
- Trusted proxies/host handling على cPanel.
- Directory listing و`.env` وGit وstorage/log exposure.
- تعطيل Docs وDebug وTelescope/Pulse خارج Local حسب المطلوب.
- صلاحيات ملفات `storage/app/private` وملف Firebase.

## 6. خطة القدرة والاستقرار

لا يبدأ Load Test قبل إصلاح SEC-03 وSEC-04، والتحقق التشغيلي من SEC-01 وSEC-02، وأخذ موافقة صريحة على الضغط.

### 6.1 سيناريوهات القياس

1. Public reads: Website، Jobs، Master Data.
2. Authentication bursts: Login وOTP، بأحجام صغيرة ومحكومة.
3. Marketplace filters والتصفح والبحث.
4. Hiring request details وقوائم العروض.
5. Upload CV وحفظ onboarding، بدون إرسال ملفات حساسة حقيقية.
6. Queue-heavy flows: Mail/FCM/Webhooks.

### 6.2 مؤشرات النجاح

- p50/p95/p99 latency.
- Requests/sec وError rate وTimeout rate.
- MySQL connections وslow queries وlock waits.
- Queue depth، عمر أقدم Job، failed jobs.
- CPU/RAM/IO/entry processes وحدود CloudLinux الفعلية.
- حجم Logs وAPI access events ونمو جداول Sessions/Cache/Jobs.

### 6.3 مخاطر الاستضافة الحالية

- الخادم الحالي Shared cPanel مؤقت، ولا يوفر SSH أوDocker أوRedis، ولا يدعم `storage:link`.
- اسم الباقة `3GPackage` لا يكفي لمعرفة RAM/CPU/IO/Entry Processes الفعلية.
- Shared hosting يجعل النتائج مرتبطة بقيود CloudLinux والجيران على الخادم.
- Database Queue وDatabase Session هما البديل العملي المؤقت في غياب Redis، لكنهما يزيدان ضغط MySQL.
- Scheduler ينفذ عدة عمليات كل دقيقة؛ يجب ضمان Cron واحد فقط من cPanel. عدم وجود SSH لا يمنع Cron إذا كانت صفحة Cron Jobs متاحة، لكن يجب معرفة مسار PHP ومسار `artisan` الصحيحين.
- إذا لم تتوفر Cron Jobs أيضًا، فلن تعمل تلقائيًا مزامنة حالات المقابلات، وانتهاء العروض، وتذكيرات الإشعارات، وتنظيف التراش، وتسوية المدفوعات.
- تشغيل `queue:work --stop-when-empty` من Maintenance Endpoint حل مؤقت وليس Worker دائمًا، ويجب استدعاؤه دوريًا من Cron محمي. وإلا ستتراكم رسائل البريد وFCM وWebhooks المؤجلة في جدول `jobs`.
- File Cache مناسب مؤقتًا لعقدة واحدة، لكنه لا يدعم توسعًا أفقيًا موثوقًا. Database Cache أكثر اتساقًا لكنه يضيف حملًا على MySQL.
- لأن `storage:link` غير مدعوم، يعتمد المشروع مؤقتًا على `PublicStorageController` لخدمة ملفات Public disk عبر `/storage/{path}`. هذا أبطأ من خدمة الملفات مباشرة من Apache/Nginx ويستهلك PHP process في كل صورة.
- الملفات الخاصة مثل CV والبطاقة الضريبية يجب أن تبقى على Private disk وتقدم فقط من Endpoints محمية، ولا تنقل إلى `public_html`.
- سجل API Key لكل طلب قد يتحول إلى Hot Write table.

### 6.4 إعداد مؤقت مناسب لخادم cPanel الحالي

```dotenv
APP_ENV=testing
APP_DEBUG=false
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
FILESYSTEM_DISK=public
```

ملاحظات:

- `APP_ENV=testing` يعني أن Maintenance Endpoint وOTP الثابتة يعملان عمدًا على هذا الخادم المؤقت.
- يجب استخدام بيانات وهمية فقط، ومفتاح API مختلف تمامًا عن Production المستقبلية.
- يجب ضبط صلاحيات الكتابة لـ`storage` و`bootstrap/cache` ومنع الوصول العام إلى `.env` و`storage/app/private`.
- يجب تنظيف جداول `jobs` و`failed_jobs` و`sessions` و`api_key_access_events` وفق Retention واضحة.
- عند الانتقال للاستضافة النهائية: `APP_ENV=production`، إزالة Maintenance HTTP flow، استخدام Worker دائم وRedis أوQueue مُدارة، وخدمة Public assets مباشرة من Web server أوObject Storage.

## 7. تحسينات الاختبارات وCI

1. Test database مستقل مع Guard يمنع اسم قاعدة غير اختباري.
2. CI jobs: PHPUnit، Composer Audit، static analysis، lint، migration up/down smoke test.
3. Feature tests سلبية للصلاحيات، لا تكتفي بالـhappy path.
4. اختبارات Contract للـresponses حتى لا تنكسر تطبيقات Frontend.
5. تشغيل OWASP ZAP baseline على بيئة اختبار مهيأة بعد تزويد حسابات اختبار.
6. فحص Secrets في Git وArtifacts وLogs.
7. Backup/restore drill دوري، وليس مجرد وجود Backup.

## 8. ترتيب التنفيذ المقترح

1. مكتمل: عزل PHPUnit نهائيًا وتشغيل 95 اختبارًا بنجاح.
2. مكتمل: تحديث الاعتماديات المصابة؛ `composer audit` بلا تنبيهات.
3. مكتمل في الكود وCompose: Maintenance وOTP حسب البيئة و`APP_DEBUG=false` إجباريًا في Testing/Production. يبقى التحقق بعد كل نشر وبناء Cache.
4. التالي: تنفيذ Authorization Matrix على Local ثم Testing.
5. معالجة SVG وتسريب رسائل 404 وسياسة سجلات API key.
6. فحص إعدادات الدومين بصورة سلبية، ثم ZAP محدود.
7. Load Test تدريجي بعد معرفة حدود الاستضافة والحصول على موافقة.

## 9. ما لم يتم بعد

- لم يتم فحص الدومين خارجيًا أوتشغيل Scanner عليه.
- لم يتم تنفيذ Load/Stress Test.
- لم يتم استرجاع قاعدة Docker المحلية؛ الاسترجاع يحتاج موافقة، ويفضل أولًا إلى قاعدة جديدة للتحقق ثم الاستبدال.
- لم تنفذ بعد مصفوفة BOLA/Authorization الكاملة ولا فحص Uploads الديناميكي.

