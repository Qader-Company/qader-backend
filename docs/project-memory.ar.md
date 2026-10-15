# ذاكرة مشروع Qader

آخر تحديث: 15 أكتوبر 2026

هذا الملف هو نقطة البداية المختصرة عند استكمال المشروع في جلسة جديدة. التفاصيل تظل في مستندات الـ architecture والـ flows المرتبطة أدناه.

## طريقة العمل المتفق عليها

- تصميم الويب في Figma هو المرجع الأساسي للشاشات والحقول وتجربة المستخدم.
- ملفات البيزنس داخل `docs/` تشرح القواعد والحالات التي لا تظهر كاملة في التصميم.
- المشروع مقسّم إلى ثلاثة أدوار رئيسية: `talent` و`client` و`admin`، ولكل دور routes وصلاحيات وواجهات API خاصة به.
- العمل يتم كسبرنتات صغيرة. في كل سبرنت نختار flow أو module جاهز، نراجع تصميمه وقواعده، ثم نعدّل الجداول والـ API الخاصة به فقط.
- لا ننفّذ كل السكيما المقترحة دفعة واحدة. ملف السكيما مرجع للمراجعة والتخطيط، وأي تغيير فعلي في migrations ينتظر مراجعة المستخدم واعتماد جداول السبرنت.
- لا ننشئ migrations جديدة لمجرد وجود جدول في السكيما المقترحة.
- لا نشغّل أو نكتب اختبارات إلا عند طلب المستخدم. نعتمد فحوصات تشغيل مركزة عند الحاجة.

## مصادر الحقيقة وترتيبها

عند وجود اختلاف نراجع المصادر بهذا الترتيب:

1. قرار المستخدم الأحدث في السبرنت الحالي.
2. Figma، خصوصًا تصميم الويب.
3. ملفات الـ flow والـ architecture المعتمدة داخل المشروع.
4. ملفات البيزنس الأصلية: BRD وPRD وSRS ومواصفات الـ flows.
5. السكيما المقترحة، وهي مسودة قابلة للتعديل وليست migrations معتمدة.

## الـ flows الرئيسية

### Talent Portal

1. إنشاء حساب Talent ثم التحقق من البريد باستخدام OTP.
2. تسجيل الدخول وإدارة access/refresh tokens وتسجيل الخروج واستعادة كلمة المرور.
3. دخول onboarding مكوّن من ثلاث خطوات ثابتة:
   - `personal-information`
   - `skills-and-experiences`
   - `work-preferences`
4. يمكن حفظ كل خطوة جزئيًا، ويعيد السيرفر حساب اكتمال الملف بعد كل حفظ.
5. عند اكتمال الخطوات الثلاث وحفظ `work-preferences` يُنشأ Talent Application داخل نفس transaction بصورة idempotent.
6. لا يوجد endpoint منفصل لإرسال الطلب في الـ flow الحالي.
7. التطبيق يحتفظ snapshot من الملف وقت إنشائه، وتعديل الملف لاحقًا لا يغيّر النسخة التي يراجعها الـ Admin تلقائيًا.
8. يظل عرض الفرص والتقديم على الوظائف والـ marketplace والعقود ضمن سبرنتات لاحقة.

التفاصيل: [Talent Registration](architecture/06-talent-registration-flow.md)، [Talent Personal Information](architecture/11-talent-personal-information.md)، [Talent Onboarding and Application](architecture/12-talent-onboarding-and-application.md).

### Admin Portal

1. إدارة البيانات المرجعية التي تعتمد عليها النماذج، مثل الدول والمدن والعملات واللغات والمجالات والتخصصات والمهارات والأدوات.
2. إدارة الأدوار والصلاحيات، مع حماية دور `super-admin` من التعديل أو الحذف عبر العمليات العادية.
3. مراجعة Talent Applications وتطبيق انتقالات الحالات المعتمدة عند بدء سبرنت المراجعة.
4. إدارة باقي وحدات المنصة، مثل العملاء وطلبات التوظيف والمقابلات والعقود والمدفوعات، كل وحدة في سبرنت مستقل عند جاهزيتها.

### Client Portal

1. تسجيل وإنشاء حساب Client من خلال نفس نظام الـ authentication مع سلوك خاص بنوع الحساب.
2. إدارة ملف العميل والـ workspace والأعضاء والصلاحيات عند بدء السبرنتات الخاصة بها.
3. إنشاء طلبات التوظيف، استقبال المرشحين والعروض، ثم المقابلات والتعاقد كـ flows مستقلة لاحقًا.
4. لا نعتبر الجداول الموسعة في السكيما منفذة قبل فتح flow العميل المرتبط بها واعتماده.

## ما تم تنفيذه أو توثيقه

- تأسيس Modular Architecture وفصل طبقات Presentation وApplication وDomain وInfrastructure.
- فصل routes حسب البورتال وتطبيق Sanctum abilities والصلاحيات المناسبة.
- تنفيذ وتوثيق authentication المشترك مع السلوك الخاص بـ Talent وClient.
- تنفيذ أجزاء Talent profile وonboarding وTalent Application الموضحة في مستندات الـ architecture الحالية.
- تجهيز modules للبيانات المرجعية وإدارتها من Admin.
- إعداد مسودة كاملة للسكيما بصيغة DBML ونسخة عربية للمراجعة وملف HTML لعرض الديجرام، بدون تحويل المقترح كله إلى migrations.
- تثبيت Scribe كاعتماد محلي فقط، ولا يتم تحميله في production.
- توليد Postman collection واحدة منظمة إلى `talent` و`client` و`admin`، وكل بورتال مقسّم إلى modules.
- توليد ثلاثة مصادر OpenAPI منفصلة لـ Apidog، واحد لكل بورتال، ويحتوي كل مصدر على endpoints البورتال كاملة بما فيها `logout` المشترك.
- تحسين استخراج request bodies وquery/path parameters، ووضع أمثلة للطلبات، وإضافة أسماء واضحة للـ endpoints ومجلدات Apidog.

## روابط توثيق الـ API المحلية

- صفحة Scribe: `http://localhost:8000/docs`
- Postman: `http://localhost:8000/docs.postman`
- Talent OpenAPI: `http://localhost:8000/docs/talent.openapi`
- Client OpenAPI: `http://localhost:8000/docs/client.openapi`
- Admin OpenAPI: `http://localhost:8000/docs/admin.openapi`

بعد تعديل routes أو Form Requests نشغّل `php artisan scribe:generate`. في Apidog يتم تحديث المصادر الثلاثة مع السماح بتحديث الاسم والـ Body والـ Parameters حتى لا يحتفظ بالبيانات القديمة. التفاصيل في [دليل Scribe وApidog](scribe-local.ar.md).

## حالة السكيما

- [database-schema-proposal.dbml](database-schema-proposal.dbml) هو ملف السكيما المقترحة القابل للمراجعة.
- [database-schema-review.ar.md](database-schema-review.ar.md) هو الشرح العربي والقرارات المفتوحة.
- [database-schema-diagram.html](database-schema-diagram.html) هو العرض المرئي للعلاقات.
- أي تعديل يطلبه المستخدم في الجداول يُحدّث أولًا في ملفات المقترح. migrations السبرنت تُنشأ فقط بعد اعتماد الجزء المطلوب.

## قاعدة بداية أي سبرنت

قبل كتابة الكود نحدد: الدور، بداية الـ flow ونهايته، الحالات والانتقالات، شاشات Figma المرتبطة، endpoints المطلوبة، الجداول الحالية المتأثرة، وأقل تعديل مطلوب في السكيما. ثم نحدّث مستند الـ flow عند اتخاذ قرار جديد.

خطة التنفيذ المرحلية موجودة في [Qader Delivery Roadmap](architecture/07-delivery-roadmap.md)، وقواعد التنفيذ في [Feature Delivery Workflow](architecture/05-feature-delivery-workflow.md).

## السبرنت الحالي

- `S00 — تأسيس المشروع`: Login وLogout والصلاحيات وتجهيز Web وMobile وBackend وFirebase/FCM.
- الحالة الحالية: سكيما الـBackend المعتمدة نُفذت وأضيفت migrations الخاصة بـ`user_devices` وtoken family.
- القرار الحالي: يرسل `fcm_token` و`platform` مع Login فقط، بدون endpoint منفصلة. نستخدم جدول `user_devices` بأقل أعمدة لازمة، ونؤجل `auth_sessions`.
- Register متاح للـTalent والـClient ببيانات الحساب الأساسية، وينشئ سجل البورتال المناسب ثم يرسل OTP. بيانات الشركة وTalent profile ضمن onboarding لاحق.
- الخطة وشكل الجداول المقترحان: [S00 Project Foundation Plan](sprints/S00-project-foundation-plan.ar.md).
