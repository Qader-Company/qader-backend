# ذاكرة مشروع Qader

آخر تحديث: 22 أكتوبر 2026

هذا الملف هو نقطة البداية المختصرة عند استكمال المشروع في جلسة جديدة. التفاصيل تظل في مستندات الـ architecture والـ flows المرتبطة أدناه.

## طريقة العمل المتفق عليها

- تصميم الويب في Figma هو المرجع الأساسي للشاشات والحقول وتجربة المستخدم.
- ملفات البيزنس داخل `docs/` تشرح القواعد والحالات التي لا تظهر كاملة في التصميم.
- المشروع مقسّم إلى ثلاثة أدوار رئيسية: `talent` و`client` و`admin`، ولكل دور routes وصلاحيات وواجهات API خاصة به. يوجد مستهلك رابع عام هو `website` لموقع الصفحات التعريفية والـMarketplace والوظائف العامة، ولا يمثل role أو مستخدمًا مسجلًا.
- العمل يتم كسبرنتات صغيرة. في كل سبرنت نختار flow أو module جاهز، نراجع تصميمه وقواعده، ثم نعدّل الجداول والـ API الخاصة به فقط.
- لا ننفّذ كل السكيما المقترحة دفعة واحدة. ملف السكيما مرجع للمراجعة والتخطيط، وأي تغيير فعلي في migrations ينتظر مراجعة المستخدم واعتماد جداول السبرنت.
- لا ننشئ migrations جديدة لمجرد وجود جدول في السكيما المقترحة.
- عند وجود migration أصلية للجدول المطلوب نعدّلها مباشرة. ننشئ migration جديدة فقط عندما لا توجد migration مناسبة للتغيير أو الجدول المطلوب.
- لا نشغّل أو نكتب اختبارات إلا عند طلب المستخدم. نعتمد فحوصات تشغيل مركزة عند الحاجة.
- كل أوامر PHP وArtisan وComposer والفحوصات الخاصة بهذا المشروع تُشغّل داخل حاوية `app` في Compose project باسم `qader-backend-local`، ولا نعتمد على PHP المحلي أو Containers مؤقتة. الصيغة الأساسية: `docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan ...`.

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
5. الحساب يستطيع امتلاك أو الانضمام لأكثر من Workspace، وكل عمليات البورتال معزولة حسب المساحة.
6. `Industry` هي Master Data مستقلة عن `professional_fields`. الموهبة تختار أكثر من Industry بجانب Skills، والـWorkspace تختار أكثر من Industry وأكثر من Professional Field عبر pivots. قيم الحجم هي `1-10`, `11-50`, `51-200`, `201-500`, `500+`.
7. أعضاء Workspace يمكن إسناد أكثر من Role لهم. منشئ المساحة يحصل على دور `owner` محمي يعامل مثل `super_admin` داخل مساحته فقط.
8. إضافة الموظفين تعتمد على دعوات بريدية، مع دعم الحساب الموجود أو إنشاء حساب Client جديد عند القبول.
9. تم تنفيذ خطة [Client Workspace and Team Foundation](sprints/client-workspace-team-foundation-implementation.ar.md): Industries، Workspaces، Dashboard، أدوار وصلاحيات محلية، أعضاء ودعوات بريدية متعددة الأدوار.
10. إضافة موظف Workspace تنشئ حساب Client وعضويته مباشرة وترسل بيانات الدخول المؤقتة بالبريد؛ لا توجد دورة دعوة أو قبول مستقلة.
11. حالة عضو Workspace هي `active` أو `suspended`، والإزالة تتم بـSoft Delete.
12. منصات روابط Workspace معرفة في `WorkspaceSocialPlatformEnum` وتتاح للواجهة من `GET /api/v1/client/workspaces/options`.

## ما تم تنفيذه أو توثيقه

- تأسيس Modular Architecture وفصل طبقات Presentation وApplication وDomain وInfrastructure.
- فصل routes حسب البورتال وتطبيق Sanctum abilities والصلاحيات المناسبة.
- تنفيذ وتوثيق authentication المشترك مع السلوك الخاص بـ Talent وClient.
- تنفيذ أجزاء Talent profile وonboarding وTalent Application الموضحة في مستندات الـ architecture الحالية.
- تجهيز modules للبيانات المرجعية وإدارتها من Admin.
- إعداد مسودة كاملة للسكيما بصيغة DBML ونسخة عربية للمراجعة وملف HTML لعرض الديجرام، بدون تحويل المقترح كله إلى migrations.
- تثبيت Scribe كاعتماد محلي فقط، ولا يتم تحميله في production.
- توليد Postman collection واحدة منظمة إلى `talent` و`client` و`admin`، وكل بورتال مقسّم إلى modules.
- توليد أربعة مصادر OpenAPI منفصلة لـ Apidog: ثلاثة للبوابات المسجلة، ومصدر `website` للواجهات العامة. تحتوي مصادر الأدوار الثلاثة فقط على `logout` و`me` المشتركين.
- تحسين استخراج request bodies وquery/path parameters، ووضع أمثلة للطلبات، وإضافة أسماء واضحة للـ endpoints ومجلدات Apidog.

## روابط توثيق الـ API المحلية

- صفحة Scribe: `http://localhost:8000/docs`
- Postman: `http://localhost:8000/docs.postman`
- Talent OpenAPI: `http://localhost:8000/docs/talent.openapi`
- Client OpenAPI: `http://localhost:8000/docs/client.openapi`
- Admin OpenAPI: `http://localhost:8000/docs/admin.openapi`
- Website OpenAPI: `http://localhost:8000/docs/website.openapi`

بعد تعديل routes أو Form Requests نشغّل `php artisan scribe:generate` داخل Docker. في Apidog يتم تحديث المصادر الأربعة مع السماح بتحديث الاسم والـ Body والـ Parameters حتى لا يحتفظ بالبيانات القديمة. التفاصيل في [دليل Scribe وApidog](scribe-local.ar.md).

## حالة السكيما

- [database-schema-proposal.dbml](database-schema-proposal.dbml) هو ملف السكيما المقترحة القابل للمراجعة.
- [database-schema-review.ar.md](database-schema-review.ar.md) هو الشرح العربي والقرارات المفتوحة.
- [database-schema-diagram.html](database-schema-diagram.html) هو العرض المرئي للعلاقات.
- أي تعديل يطلبه المستخدم في الجداول يُحدّث أولًا في ملفات المقترح. migrations السبرنت تُنشأ فقط بعد اعتماد الجزء المطلوب.

## قاعدة بداية أي سبرنت

قبل كتابة الكود نحدد: الدور، بداية الـ flow ونهايته، الحالات والانتقالات، شاشات Figma المرتبطة، endpoints المطلوبة، الجداول الحالية المتأثرة، وأقل تعديل مطلوب في السكيما. ثم نحدّث مستند الـ flow عند اتخاذ قرار جديد.

مرجع قيم الـenums المستخدمة في عقود الـAPI موجود في [API Enums Reference](api-enums-reference.ar.md)، ويجب تحديثه في نفس التغيير عند إضافة enum أو قيمة جديدة أو تعديل قيمة حالية.

قاعدة توافق الـAPI: لا نغير Path أو HTTP Method أو نزيل/نعيد تسمية حقل قائم في endpoint مرتبطة بالـFrontend. التطوير يكون بإضافة endpoints أو حقول جديدة، مع إبقاء العقود القديمة مثل `status`, `interview` و`meeting_url` متاحة لتجنب كسر التكامل الحالي.

خطة التنفيذ المرحلية موجودة في [Qader Delivery Roadmap](architecture/07-delivery-roadmap.md)، وقواعد التنفيذ في [Feature Delivery Workflow](architecture/05-feature-delivery-workflow.md).

## السبرنت الحالي

- `S02B — استكمال المقابلات والقرار النهائي`: تم اعتماد وتنفيذ الخطة في [S02B Talent Interview Completion Plan](sprints/S02B-talent-interview-completion-plan.ar.md)، والتفاصيل والأمثلة في [S02B Talent Interview Completion Implementation](sprints/S02B-talent-interview-completion-implementation.ar.md). تبدأ من المقابلة المؤكدة، وتضيف تعدد الجولات وحالات البدء والإكمال وتقييم كل مقابلة، ثم القبول أو الرفض أو جدولة Round جديدة. الانتقال إلى `in_progress` و`completed` يعمل تلقائيًا حسب الوقت مع Actions يدوية، ونافذة Join من 15 دقيقة قبل البداية حتى نهاية الموعد. المسؤول المعيّن في `talent_applications.assigned_admin_id` هو الوحيد الذي يدير المقابلة ويتخذ قراراتها مع امتلاك الصلاحية، بينما أصحاب صلاحية العرض يرون All Interviews بدون Actions وMy Interviews هي طلباته المعيّنة. طبقًا لـFigma تعرض الموهبة تقارير التقييم و`final_feedback`، ولا نضيف `decision_feedback` مستقلًا. Interviews موديول صلاحيات مستقل. تشمل كذلك إدارة حالات المواهب النهائية في Admin Talents: Accepted وRejected وBlacklisted، مع `available_actions` حسب الصلاحيات بالأسماء `add_to_blacklist`, `remove_from_blacklist`, `edit_reapply`. الـBlacklist حالة فعالة مستقلة ولا تضاف إلى `TalentApplicationStatusEnum`. لا يشمل السبرنت نشر الموهبة في Marketplace أو Hiring Requests الخاصة بالعميل.
- `S03 — النماذج الخارجية للوظائف`: تم تنفيذ Backend كاملًا طبقًا لشاشات Job Forms في Admin Portal وصفحات الوظائف والنموذج العام ونجاح التقديم. الخطة المعتمدة في [S03 External Job Forms Plan](sprints/S03-external-job-forms-plan.ar.md)، وما تم تنفيذه وخطوات التطبيق وأمثلة الـAPI في [S03 External Job Forms Implementation](sprints/S03-external-job-forms-implementation.ar.md).
- تنفيذ S03 يشمل `job_postings` كـaggregate واحد، الإصدارات المنشورة الثابتة، حقول `professional_form_fields`، التقديم العام والـCV الخاص، إدارة التقديمات وCSV، و`Start Hiring Process` مع الحساب المؤقت وإجبار تغيير كلمة المرور. Public Jobs موثقة في Website OpenAPI مستقل تحت `website/jobs`.
- Marketing Website يستهلك `GET /api/v1/public/jobs` للقائمة، و`GET /filters` لاختيارات الفلاتر، و`GET /{job}` للتفاصيل، و`GET /{job}/application-form` لبناء النموذج، و`POST /{job}/submissions` للتقديم. القائمة والتفاصيل ترجع Skills المجال، و`public_url` يعتمد على `APP_URL`. فلاتر المجال ونوع الوظيفة ونمط العمل مفعلة، وخيارات نطاق الخبرة ترجع من الـenum لكن فلترة النتائج بها مؤجلة حتى تخزن الوظيفة هذا النطاق.
- مصدر حقول S03 هو `professional_form_fields` المرتبط بالمجال من خلال `professional_field_form_field`؛ لن ننشئ `job_question_catalog`. اختيار الحقول يعمل بنفس طريقة الفورمز الداخلية: الـFrontend يستقبل keys ويملك العرض والترتيب، والBackend يطبق Validation خارجيًا حسب الـkey. يحفظ كل version مراجع الحقول المختارة فقط في pivot مركب من غير تكرار label أوtype أوrequired أوvalidation في قاعدة البيانات.
- S03 يشمل Draft وPublished، إصدارات ثابتة، Activation مستقل، الرابط العام، تقديم الزائر والـCV، قائمة وتفاصيل التقديمات وCSV export.
- عنوان Job Form يتولد من المسمى الوظيفي ولا يرسل كحقل مستقل. Copy Link يفتح تفاصيل الوظيفة داخل Explore Open Positions ثم ينتقل الزائر من Apply Now إلى النموذج. إلزامية الأسئلة ثابتة حسب Figma؛ يعرضها ويتحقق منها Frontend ويطبق Backend Validation مطابقًا كمرجع نهائي. يسمح بتكرار البريد في تقديمات النموذج.
- يسمح بنشر Job Form بالحقول الأربعة الثابتة فقط، والأسئلة الإضافية اختيارية.
- الحقول الثابتة النهائية في نموذج التقديم الخارجي هي Applicant Name وEmail وMobile Number وCV؛ المتقدم لا يدخل Job Title لأن الوظيفة معروفة من الرابط. اختيار Published ينشر ويفعّل الرابط مباشرة.
- نطاقات الخبرة ومدد الإشعار تكون enums ثابتة، ومدد الإشعار بالقيم `0`, `7`, `14`, `30`, `60`, `90` يومًا. اختيار حقل الراتب المتوقع يعرض المبلغ والعملة معًا، وتأتي العملة من `currencies` الفعالة.
- مهارات الوظائف العامة وخيارات سؤال `skill_ids` تأتي من Skills الفعالة المرتبطة بـ`professional_field_id` عبر `professional_field_skill`؛ لا ننشئ `job_posting_skills` في S03، ولذلك كل وظائف المجال تعرض مهارات المجال الحالية. لا نضيف `seniority_level`, `country_id` أو`city_id` لأنها غير موجودة في Form Setup. يدعم البحث العام العنوان والوصف ومهارات المجال، وفلتر `skill_id` يعمل من خلال علاقة المجال.
- قائمتا التقديمات في Figma تمثلان حالتين مكملتين، وكل الأكشنز الظاهرة داخلة في S03: Search وFilter وExport وStatus وView وDownload CV وحذف فردي وStart Hiring Process. حذف submission يكون Soft Delete للحالة `received` فقط، ويرفض بعد بدء Hiring Process. تحديد الصفوف وClear selection سلوك Frontend، ولا يوجد Bulk Delete لعدم وجود زر تنفيذ جماعي في التصميم.
- `Start Hiring Process` في S03 يحول التقديم المناسب إلى حساب Talent: ينشئ الحساب إن لم يوجد أو يربط الموجود دون تغيير كلمة مروره، ويرسل للحساب الجديد كلمة مرور عشوائية مؤقتة على البريد. أول Login يطلب تغييرها قبل إصدار Access Token كامل، ثم تكمل الموهبة Onboarding الحالي. لا ينشأ Talent Application تلقائيًا من التقديم الخارجي.
- `S02 — تقييم المواهب`: قائمة المراجعة، تعيين المراجع وإعادة تعيينه، تقييم الملف، رفض الطلب أو إنشاء تفاصيل المقابلة والـslots. تمت مراجعة Figma وقاعدة البيانات والكود، والخطة المحدثة موجودة في [S02 Talent Evaluation Plan](sprints/S02-talent-evaluation-plan.ar.md).
- تم تنفيذ Backend الخاص بـS02 وتوثيقه في [S02 Talent Evaluation Implementation](sprints/S02-talent-evaluation-implementation.ar.md): workflow التعيين والتقييم والقرار، منع تداخل المقابلين، تدفق الموهبة، إدارة FAQs وendpoints منفصلة لقيم enums، مع توثيق Admin OpenAPI كامل.
- حدود S02 تنتهي عند إنشاء المقابلة والـslots وتحويل الطلب إلى `interview_required`، أو عند `rejected`. اختيار الموعد وتنفيذ المقابلة والقبول النهائي خارج السبرنت.
- التقييم ثابت على أربعة معايير من 1 إلى 5، ويجب إكمال المعايير الأربعة. الرواتب والتقارير والنصائح اختيارية مع Validation كامل عند إرسالها.
- الضغط على Evaluate لطلب غير معيّن يعيّن المستخدم الحالي مراجعًا تلقائيًا. إذا كان معيّنًا لغيره يحتاج Reassign. كل أصحاب صلاحية العرض يرون All Applications وMy Applications دون الاعتماد على اسم الـrole.
- سياسة إعادة التقديم ترسل `reapply_after_months` ويحسب السيرفر `reapply_at` من وقت الرفض. `blacklist_reason` مطلوب فقط مع سياسة `blacklisted`.
- `interview_slots` يخزن `starts_at` و`ends_at`. الواجهة لا تتغير؛ السيرفر يحسب النهاية افتراضيًا بعد 60 دقيقة من البداية. موقع الخريطة يخزن `latitude` و`longitude` بدل رابط خريطة.
- `Automatic Slots` ملغي؛ المواعيد تُضاف يدويًا. قبل الجدولة يجب التأكد أن كل interviewer غير مشغول في أي فترة متداخلة، مع قفل المعنيين داخل transaction لمنع التعارض المتزامن.
- لا يُنشأ Google Meet لكل موعد مقترح. بعد اختيار الموهبة slot واحدًا ينشئ السيرفر Google Calendar Event وMeet، ثم يحفظ `google_calendar_event_id` و`meeting_url` على `interviews`.
- تدفق الموهبة المقابل لـS02 منفذ من `GET /api/v1/talent/application`: يرجع `screen_state` والحالة الحالية والمقابلة والمواعيد والأفعال المتاحة وFAQs الخاصة بالشاشة الحالية.
- الموهبة تستطيع رفض مجموعات مواعيد المقابلة مرتين فقط. كل رفض يخزن السبب ويزيد `slot_rejection_count`، والاستجابة ترجع المستخدم والمتبقي من الحد. بعد الرفض يرسل الأدمن مواعيد بديلة من endpoint مستقل.
- إلغاء الموعد المؤكد وطلب إعادة الجدولة متاح مرة واحدة فقط، ويحذف Calendar Event قبل انتظار المواعيد البديلة.
- FAQs الخاصة بمراحل مراجعة الطلب تُدار من الأدمن بالعربية والإنجليزية. كل FAQ لها `screen_states` تحدد بدقة الشاشات التي تظهر فيها؛ السؤال المشترك يمكن ربطه بأكثر من شاشة دون تكراره.
- Enums التي يختار منها Admin Frontend لها endpoints منفصلة حسب الموديول، وليست endpoint موحدة: حالات شاشة FAQ تحت `admin/talent-review-faqs/screen-states`، وباقي حالات وخيارات التقييم والمقابلة تحت `admin/talent-applications/*`.
- الحظر ليس حالة للطلب؛ الطلب يكون `rejected` مع سجل نشط في `talent_blacklist_entries`.
- `S01 — تسجيل المواهب` تم تنفيذه: التسجيل والملف المهني والحقول المتغيرة حسب المجال والـMaster Data اللازمة للنموذج، حتى إرسال الملف للمراجعة بنجاح.
- `S00 — تأسيس المشروع` مؤجل مؤقتًا وسنرجع لإغلاق عناصر Web/Mobile وFirebase المتبقية لاحقًا.
- تمت مراجعة شاشات الويب الأساسية في Figma والكود والمigrations والسكيما المقترحة ووثائق البيزنس.
- نُفذ Backend الخاص بـS01 ومحاذاته مع Figma: شاشة اختيار المجال، موضع CV وProfessional Summary، Draft مقابل Next، seed data للمجالات، snapshot للتقديم، وحماية الملفات.
- معنى الحقول الديناميكية في S01 هو اختيار input keys لكل Professional Field من كتالوج ثابت. الـBackend يرجع keys فقط، والفرونت يحدد الخطوة والترتيب والlabel ونوع العرض وعلامة Required. قواعد validation والحفظ الأساسية تظل في كود الـBackend.
- لا نحتاج `professional_fields.display_order`. أُعيد اعتماد `professional_field_translations.description` لاحقًا.
- نُفذ تبسيط `professional_form_fields` داخل migration الإنشاء الأصلية: لا توجد `step`, `is_default`, `is_required_by_default`، ولا `is_required` في `professional_field_form_field`.
- أُعيد اعتماد `professional_field_translations.description` بقرار لاحق، وتُرجع Talent API الوصف المحلي بينما تُرجع Admin API الوصف بالإنجليزية والعربية.
- نُفذ S01: اختيار المجال منفصل، CV في Basic Info، Professional Summary في Skills & Experience، البريد للعرض فقط، حفظ Draft منفصل عن Submit، snapshot ونسخة CV ثابتة عند التقديم، وملفات الموهبة على private disk بتنزيل محمي.
- `GET /api/v1/talent/onboarding/application` يرجع `404` برسالة مترجمة واضحة عند عدم وجود تقديم، و`200` مع تفاصيل الطلب عند وجوده.
- جميع مسارات الـAPI الحالية، بما فيها Auth وTalent وAdmin، تتطلب `X-Authorization` وتتحقق منه عبر middleware `api.key`. ملفات Scribe/OpenAPI/Postman توثّق الهيدر تلقائيًا.
- `DemoAccountSeeder` ينشئ حسابات ثابتة للـTalent وClient وAdmin (`talent@qader.test`, `client@qader.test`, `admin@qader.test`) بكلمة المرور `Password123!`، وحساب Admin يحمل دور `owner`. أمثلة Auth في Scribe ثابتة وتستخدم هذه الحسابات.
- أمثلة Scribe لكل الـendpoints ثابتة في `DocumentationExamples` وتشمل body وquery وpath parameters وطلبات multipart؛ لا نعتمد على قيم Faker في الملفات التي يستوردها Apidog.
- ملفات Apidog/OpenAPI المحلية تُولد بـ`php artisan scribe:generate`، ومصادر الاستيراد هي `/docs/talent.openapi` و`/docs/client.openapi` و`/docs/admin.openapi` و`/docs/website.openapi`.
- Master Data داخل S01 تشمل Countries وCities وLanguages وCurrencies وProfessional Fields وJob Titles وSkills وTools، بالإضافة إلى Talent enums. الموديولات وواجهات Admin والـseed data المطلوبة منفذة.
- تمت مراجعة واعتماد سكيما S01، ودُمجت التغييرات في migrations الإنشاء الموجودة بدون migration تجميعية إضافية.
- المراجعة والخطة وشكل الجداول المقترح: [S01 Talent Registration Plan](sprints/S01-talent-registration-plan.ar.md).
- عناصر S00 المؤجلة تظل مسجلة في [S00 Project Foundation Plan](sprints/S00-project-foundation-plan.ar.md).
