# تقرير التحقق من مشاكل الـ API والـ Flows

تاريخ المراجعة: 12 نوفمبر 2026

## تحديث التنفيذ والتحقق

تم بعد إعداد التقرير إكمال حزمة الإصلاحات الآمنة دون حذف أوتغيير أسماء أي Endpoints قائمة:

- منع الرد على عرض عنصر ملغي أوخارج مرحلة العرض، ومنع إعادة العرض من إحياء عنصر مغلق أوموقوف.
- إعادة `404` للموهبة الموثقة التي لم تقدم Application بدل `403`.
- معالجة انتهاء كل Interview Slots وإتاحة `offer_new_slots` للأدمن.
- تحويل تعطل إنشاء Google Meet عند اختيار الموعد من `503` إلى `422` برسالة Validation واضحة مع إبقاء الموعد متاحًا.
- فلترة Skills وTools تلقائيًا بمجال الموهبة، وإرجاع شرط `experiences` ضمن `conditional_required_fields`.
- توثيق قيم `offers/respond-on-behalf` (`accepted`, `rejected`) واشتراط سبب الرفض.
- إضافة رد الأدمن على موعد مقابلة التوظيف بالنيابة عن العميل أوالموهبة:
  `POST /api/v1/admin/hiring-requests/items/{item}/interview/respond-on-behalf`.
- إضافة بيانات العرض اللازمة فقط إلى Responses الأدمن: بيانات تواصل العميل، وكود وبيانات خبرة الموهبة.
- إضافة اختبارات Regression للحالات السابقة.

نتيجة التحقق داخل Docker project الفعلي `qader-backend-local`:

- **50 اختبارًا ناجحًا.**
- **329 assertion ناجحة.**
- `git diff --check` وPHP syntax checks ناجحة.
- تم توليد OpenAPI بنجاح، وتم التحقق أن `http://localhost:8000/docs/admin.openapi` يعرض المسار الجديد وBody enums الصحيحة.
- التغيير الوحيد في مسارات الـAPI هو **إضافة** مسار رد الأدمن على المقابلة؛ لم يحذف أويتغير أي مسار قائم.

## نطاق المراجعة

تمت مراجعة المشكلات الظاهرة في الصور مقابل:

- تعريفات الـ Routes الحالية.
- قواعد الـ Validation والـ Form Requests.
- Services وWorkflows المسؤولة عن تغيير الحالات.
- Resources و`available_actions`.
- ملفات OpenAPI المولدة محليًا لـ Apidog.
- الاختبارات الموجودة حاليًا.

بدأ المستند كتقرير تحقق فقط، ثم تم تحديثه بعد تنفيذ واختبار الإصلاحات الموضحة أعلاه.

## ملاحظة مهمة عن حالة الكود

يوجد حاليًا في الـ working tree تعديلات غير ملتزم بها في Git تعالج المشكلات المذكورة أعلاه. لذلك يجب التفريق بين:

- **الكود المنشور أو آخر Commit:** قد تظل المشكلة موجودة فيه.
- **الكود المحلي الحالي:** الإصلاحات مؤكدة بحزمة الاختبارات الكاملة باستخدام SQLite معزولة داخل Docker.

أهم الإصلاحات المحلية غير الملتزم بها تخص: انتهاء مواعيد مقابلة طلب الموهبة، اختيار الموعد عند تعطل Google Calendar، الرد على العرض داخل عنصر ملغي، قراءة طلب الموهبة قبل أول تقديم، فلترة Skills وTools، وإظهار شرط `experiences`.

## الملخص التنفيذي

| التصنيف | العدد | الملخص |
| --- | ---: | --- |
| مشكلة مؤكدة أو Flow ناقص | 8 | أهمها رد الأدمن على موعد مقابلة التوظيف بالنيابة، إلزام أسباب Hold/Cancel، Client Help Requests list، ونقص بعض بيانات قوائم الأدمن |
| تم علاجها في الكود المحلي غير الملتزم | 6 | تشمل المشاكل 1 و2 و3 و4 و6 و7 كليًا أو جزئيًا |
| مشكلة توثيق أو Contract غير واضح | 5 | Assign Help Request، Closure outcome، قيم Respond on behalf، حقول Workspace، وبعض فلاتر القوائم |
| البلاغ قديم أو غير دقيق مع الكود الحالي | 5 | Reschedule مقابلة التوظيف، إنشاء/Re-offer العرض، `status_label`، فلاتر Help Requests، وTax Card للأدمن |

الأولوية الأولى ليست إضافة Endpoints عشوائيًا؛ المطلوب أولًا تثبيت الإصلاحات المحلية، إضافة اختبارات لها، إعادة توليد OpenAPI، ثم معالجة الـflows المؤكدة الناقصة.

## التحقق من المشكلات المرقمة في الصور

### 1. اختيار موعد مقابلة قريب يعيد 503

**النتيجة: مشكلة حقيقية جزئيًا، والإصلاح المحلي يعالج سبب 503 فقط.**

- الـ Endpoint الصحيح هو `POST /api/v1/talent/application/interview/slots/{slot}/select`.
- الكود الأصلي كان يستدعي Google Calendar عند اختيار Online Slot دون `meeting_url` يدوي. تعطل المزود كان يخرج كـ503.
- التعديل المحلي في `TalentInterviewWorkflow` يحول تعطل Google Calendar إلى 422 برسالة واضحة على الحقل `slot` بدل 503.
- لا توجد حاليًا قاعدة تمنع اختيار موعد بعد دقائق قليلة طالما أنه في المستقبل. لذلك إذا كان المطلوب Business Rule مثل حد أدنى 30 أو 60 دقيقة، فهذا القرار غير موجود في الكود ولا في Platform Settings.

**المطلوب:** اعتماد حد أدنى قبل الموعد إن كان مطلوبًا، وإضافة اختبارين: مزود Calendar غير متاح، وSlot داخل الحد الأدنى.

**الدليل:**

- `app/Modules/V1/TalentApplications/Application/Services/TalentInterviewWorkflow.php`
- `routes/v1/talent/application.php`

### 2. انتهاء كل Slots يترك الطلب في Dead End

**النتيجة: البلاغ صحيح في النسخة السابقة، ومُعالج محليًا حاليًا.**

التعديل المحلي:

- يكتشف أن كل الـavailable slots أصبحت في الماضي عبر `Interview::hasOnlyExpiredSlots()`.
- يعرض للموهبة `screen_state = awaiting_new_interview_slots`.
- يعيد للأدمن Action باسم `offer_new_slots`.
- يستخدم الـEndpoint الموجود بالفعل لإرسال مواعيد جديدة:
  `POST /api/v1/admin/talent-applications/{application}/interview/slots`.

**تمت إضافة Feature Test صريح** لحالة انتهاء جميع المواعيد دون تدخل الموهبة، والمتبقي Commit وDeploy.

**الدليل:**

- `app/Modules/V1/TalentApplications/Domain/Models/Interview.php`
- `app/Modules/V1/TalentApplications/Presentation/Http/Resources/TalentApplicationResource.php`
- `app/Modules/V1/TalentApplications/Application/Services/TalentApplicationQueryService.php`
- `app/Modules/V1/TalentApplications/Application/Services/AdminInterviewQueryService.php`

### 3. Respond on behalf يقبل عنصرًا ملغيًا ويعيده إلى Interview

**النتيجة: Bug مؤكد في النسخة السابقة، ومُعالج محليًا.**

سبب المشكلة كان شرطيًا:

- الـWorkflow كان يتحقق من حالة آخر Offer فقط ولا يتحقق أن العنصر نفسه ما زال في مرحلة `offer`.
- شرط `available_actions` كان يسمح للعرض المنتهي بإظهار Action حتى لو كان العنصر `cancelled` بسبب أولوية عوامل `&&` و`||`.

التعديل المحلي:

- يشترط `HiringRequestItemStatusEnum::OFFER` قبل الرد بالنيابة.
- يمنع Workspace غير نشطة.
- يصلح شرط `available_actions`.
- النتيجة المتوقعة الآن 409 بدل 200 للعناصر الملغاة أو الموقوفة أو المنتهية.

**تمت إضافة Feature Test** يغطي العنصر `cancelled` ويثبت بقاء حالته وعدم ظهور Action الرد. منطق الـWorkflow نفسه يمنع كل حالة خارج `offer` ومنها `on_hold`.

**الدليل:**

- `app/Modules/V1/HiringRequests/Application/Services/HiringOfferWorkflow.php`
- `app/Modules/V1/HiringRequests/Presentation/Http/Resources/HiringRequestItemResource.php`

### 4. GET Talent Application يعيد 403 قبل أول تقديم

**النتيجة: مُعالج محليًا بالنسبة للموهبة الموثقة، أما 401 قبل توثيق الحساب فهو سلوك مقصود.**

- التعديل المحلي نقل `GET /api/v1/talent/application` و`GET /api/v1/talent/application/options` خارج Permission `platform_access` مع الإبقاء على `auth:sanctum` وTalent access token.
- موهبة موثقة لم تقدم طلبًا ستحصل على 404 ورسالة `No application has been submitted yet.`.
- `GET /api/v1/talent/onboarding/application` كان يعيد 404 بالفعل.
- الموهبة غير الموثقة لا تملك access token كاملًا؛ لذلك 401 على Talent endpoints طبيعي ضمن Auth Flow الحالي، وليست حالة Application.

**ملاحظة:** يوجد Endpointان يعيدان نفس الطلب: `/talent/application` و`/talent/onboarding/application`. يفضل اعتماد واحد للفرونت لتقليل الالتباس، دون حذف القديم قبل مراجعة الارتباطات.

**الدليل:**

- `routes/v1/talent/application.php`
- `routes/v1/talent/onboarding.php`
- `app/Modules/V1/TalentApplications/Application/UseCases/GetTalentApplicationUseCase.php`

### 5. Workspace الشركة لا توثق Tax Card وCR Number

**النتيجة: الحقول موجودة في الكود، لكن Validation والتوثيق المولد غير مكتملين.**

- الأسماء الصحيحة الحالية: `cr_number` و`tax_card`.
- `tax_card` يقبل PDF أو PNG أو JPEG حتى 10MB.
- الحقلان حاليًا `nullable` دائمًا حتى عندما `is_company = true`؛ إذًا Branch الشركة لا يفرضهما كما تقول الشاشة.
- ملف OpenAPI الحالي لا يحتوي الحقلين، ما يعني أنه قديم ولم يُعد توليده بعد إضافة الحقول.
- Tax Card متاحة للعميل وللأدمن عبر Endpoints تنزيل موجودة بالفعل.

**المطلوب:** إذا كان التصميم يجعل الحقلين إلزاميين للشركة، استخدام `required_if:is_company,true`، ثم إعادة توليد Scribe وImport إلى Apidog.

**الدليل:**

- `app/Modules/V1/Workspaces/Presentation/Http/Requests/StoreWorkspaceRequest.php`
- `routes/v1/client/workspaces.php`
- `routes/v1/admin/clients.php`
- `storage/app/private/scribe/client.openapi.yaml`

### 6. `experiences` مطلوبة عند الحفظ ولا تظهر ضمن required fields

**النتيجة: المشكلة كانت صحيحة، ويوجد علاج محلي غير ملتزم.**

- `experiences` مطلوبة بشكل شرطي عندما `years_of_experience > 0`.
- التعديل المحلي يضيف `conditional_required_fields` إلى حالة Onboarding بالشكل المفاهيمي: `experiences` مطلوبة عندما يكون `years_of_experience` أكبر من صفر.
- `TalentOnboardingResource` يعيد بيانات الخطوات كاملة، لذلك يظهر الشرط في `GET /talent/onboarding`.
- `TalentProfileResource` أصبح يعيد `conditional_required_fields` أيضًا، ولذلك اتحد شكل الـmetadata بين Profile وOnboarding.

**المطلوب:** تحديد Endpoint المصدر للـform schema، وتوحيد نفس metadata في الموردين أو جعل الفرونت يعتمد على Onboarding فقط.

**الدليل:**

- `app/Modules/V1/Talents/Application/Services/TalentProfileProgressService.php`
- `app/Modules/V1/Talents/Presentation/Http/Resources/TalentOnboardingResource.php`
- `app/Modules/V1/Talents/Presentation/Http/Resources/TalentProfileResource.php`

### 7. Skills وTools تعيد كل القيم ثم Submit يرفضها

**النتيجة: المشكلة صحيحة في النسخة السابقة، ومُعالجة محليًا للحساب الذي اختار مجاله.**

- التعديل المحلي يجعل `professional_field_id` الافتراضي هو مجال الموهبة المسجل إذا لم يرسله الفرونت.
- يمكن للفرونت الاستمرار بإرسال `professional_field_id` صراحة.
- إذا لم تكن الموهبة قد اختارت مجالًا بعد، ستظل القائمة غير مقيدة؛ لذلك ترتيب الـflow يجب أن يكون اختيار المجال أولًا ثم تحميل Skills وTools.

**الدليل:**

- `app/Modules/V1/Skills/Presentation/Http/Requests/ListActiveSkillsRequest.php`
- `app/Modules/V1/Tools/Presentation/Http/Requests/ListActiveToolsRequest.php`

### 8. `POST help-requests/{id}/assign` يعيد 404

**النتيجة: ليست مشكلة تنفيذ؛ المسار المستخدم غير موجود، لكن Contract الـActions غير كافٍ.**

المسارات الحالية هي:

- Assign لطلب أو أكثر: `POST /api/v1/admin/help-requests/assign` مع `help_request_ids[]` و`admin_id`.
- Reassign لطلب واحد: `POST /api/v1/admin/help-requests/{id}/reassign` مع `admin_id`.

`available_actions` يعيد أسماء مجردة مثل `assign` و`reassign` ولا يعيد Route أوطريقة التنفيذ؛ لذلك تفسير الفرونت لـ`assign` كمسار تحت `{id}` مفهوم لكنه غير صحيح.

**المطلوب:** توثيق Action-to-endpoint mapping صراحة. لا أوصي بإضافة Alias جديد قبل اعتماد شكل موحد لكل `available_actions`.

**الدليل:**

- `routes/v1/admin/help-requests.php`
- `app/Modules/V1/HelpRequests/Application/Services/AdminHelpRequestService.php`

### 9. إغلاق Help Request بنتيجة New Hiring Request يحتاج ID غير ظاهر

**النتيجة: منطق الباك صحيح، لكن خيارات الإدخال الشرطية غير واضحة في Options.**

- عند `outcome = new_hiring_request_created` يصبح `hiring_request_id` إلزاميًا.
- الباك يتحقق أن الطلب تابع لنفس Workspace.
- يوجد Endpoint مخصص لجلب الاختيارات الصحيحة:
  `GET /api/v1/admin/help-requests/{id}/hiring-requests`.
- `GET .../options` يعيد Outcomes فقط ولا يشرح أن هذا Outcome يحتاج `hiring_request_id` ولا يربطه بالـEndpoint السابق.

**المطلوب:** إضافة metadata شرطية في Options أو توثيقها في OpenAPI واسم الـEndpoint بوضوح.

**الدليل:**

- `CloseHelpRequestRequest`
- `AdminHelpRequestController::hiringRequests`
- `AdminHelpRequestService::closableHiringRequests` و`close`

### 10. قيم Decision في Offer Respond on behalf غير موثقة

**النتيجة: مشكلة توثيق مؤكدة.**

- القيم المقبولة فعليًا هي `accepted` و`rejected` فقط.
- عند `rejected` يكون `reason` إلزاميًا في الكود.
- OpenAPI الحالي يعرض `decision` كـstring دون `enum`، وبالتالي Apidog لا يوضح القيم.
- Options الحالية تعرض جميع Offer statuses، ومنها `pending` و`countered` و`expired`، وهي ليست كلها قرارات مسموحة لهذا Endpoint.

**تم التنفيذ:** نُقل Validation إلى FormRequest مخصص مع `Rule::in` و`bodyParameters`، ويعرض OpenAPI القيمتين فقط لهذا الـEndpoint دون تغيير Options العامة أوالـResponse.

**الدليل:**

- `AdminHiringWorkflowController::respondOnBehalf`
- `storage/app/private/scribe/admin.openapi.yaml`

### 11. Resume Parse يعيد 503 وأحيانًا Suggestion واحدة

**النتيجة: مشكلة Reliability وجودة مطابقة مؤكدة وليست خطأ عشوائيًا في Controller.**

- الـ503 مقصودة عندما Gemini غير متاح أو يفشل الاتصال.
- لا يوجد Retry أوBackoff أوFallback model/provider حاليًا.
- Mapper يطابق Job Titles وSkills وTools واللغات بمساواة نصية بعد normalization فقط؛ أي اختلاف اسم أومرادف يمنع المطابقة، فتعود IDs فارغة رغم أن Gemini استخرج النص.
- الرد يعيد الحقول المطابقة فقط ولا يعيد `unmatched`، بناءً على القرار السابق لتبسيط Response للفرونت.

**المطلوب بالترتيب:**

1. Retry محدود مع exponential backoff للـ429/503/connection errors.
2. Fallback إلى Model أوProvider ثانٍ.
3. Alias matching ثم fuzzy matching بحد ثقة واضح للـMaster Data.
4. الاحتفاظ بأسماء الحقول الحالية للفرونت، ويمكن إضافة `warnings` اختيارية بدل تغيير الشكل.

**الدليل:**

- `GeminiResumeParserGateway`
- `ResumeAutocompleteMapper`
- `TalentResumeParserController`

### 12. قائمة Hiring Offers لا تدعم فلتر `active`

**النتيجة: البلاغ صحيح كاختلاف Contract، لكن توجد فلاتر تركيبية بالفعل.**

- لا يوجد Boolean باسم `active` أوScope باسم `active`.
- يوجد `status[]` لحالة العنصر و`offer_status[]` لحالة آخر عرض.
- يمكن تمثيل العرض النشط غالبًا بـ`status[]=offer&offer_status[]=pending`، لكن هذا تعريف غير معلن ويجبر الفرونت على معرفة تفاصيل الـstate machine.
- ملف OpenAPI الحالي قديم ويعرض حالات عناصر سابقة مثل `awaiting_talent_response` بدل Enum الحالية المبسطة (`pending`, `offer`, `interview`, ...).

**المطلوب:** إما اعتماد `scope=active` وتعريفه بدقة، أوتوثيق التركيبة الرسمية. وفي الحالتين يجب إعادة توليد OpenAPI.

**الدليل:**

- `ListTalentHiringOffersRequest`
- `HiringOfferWorkflow::paginateForTalent`
- `HiringRequestItemStatusEnum`
- `storage/app/private/scribe/talent.openapi.yaml`

## التحقق من الـFlows المذكورة كمفقودة

### Reschedule مقابلة التوظيف المؤكدة

**موجودة بالفعل؛ البلاغ قديم أو لم يُربط في الواجهة.**

- الأدمن يستخدم نفس Endpoint الجدولة:
  `POST /api/v1/admin/hiring-requests/items/{item}/interview/schedule`.
- `HiringInterviewWorkflow::propose` يسمح بإعادة الجدولة ما لم تكن المقابلة `in_progress` أو`completed`.
- يحذف Google event السابق، يعيد رد الطرفين إلى `pending`، ويسجل Event باسم `interview_rescheduled`.
- `HiringInterviewResource` يعيد `reschedule_interview` للأدمن عندما تكون المقابلة Confirmed.

يوجد اختبار سابق يغطي اعتذار الموهبة بعد التأكيد وإعادة الجدولة بواسطة الأدمن.

### رد الأدمن على موعد مقابلة التوظيف بالنيابة عن العميل أوالموهبة

**تم تنفيذ الـFlow واختباره.**

المسار الجديد:

`POST /api/v1/admin/hiring-requests/items/{item}/interview/respond-on-behalf`

ويستقبل `party = talent|client` و`response = accepted|change_requested|apologized`، ويشترط reason للحالتين الأخيرتين. يستخدم نفس منطق الرد الأصلي، ويتحقق أن الأدمن مسؤول عن الطلب، ويسجل المستخدم الإداري الحقيقي في Events.

### Hold وCancel دون Reason

**البلاغ صحيح جزئيًا.**

- الباك يخزن `hold_reason` و`cancellation_reason` ويسجل Events؛ إذًا الـaudit موجود.
- الـController يجعل `reason` nullable، رغم القرار السابق بالحاجة إلى سبب Hold وسبب Cancel.

**المطلوب:** جعل reason required في Admin وClient actions إذا ما زال هذا القرار معتمدًا. رسالة التأكيد نفسها مسؤولية الواجهة وليست الـAPI.

### إنشاء أوتعديل أوإعادة إرسال Offer من الأدمن

**الـEndpoint موجود، لكن الـpayload ثقيل ولا يوجد PATCH.**

- `POST /api/v1/admin/hiring-requests/items/{item}/offers` يرسل عرضًا منقحًا أوRe-offer داخل مرحلة `offer`.
- الكود يحتفظ بتاريخ Offers؛ لذلك عدم وجود Edit in-place متسق مع التصميم الحالي للتفاوض.
- الـEndpoint يطلب تفاصيل العرض كاملة بدل إرسال الحقول التي تغيرت فقط.

المشكلة هنا UX/API ergonomics وليست غياب Endpoint. يمكن للواجهة Prefill من آخر Offer، أويمكن لاحقًا دعم partial override مع نسخ القيم غير المرسلة من آخر Offer دون تعديل السجل القديم.

### بيانات طلب التعيين في الأدمن

**نقص جزئي مؤكد.**

الموجود حاليًا:

- Workspace: `id`, `name` فقط.
- Talent داخل العنصر: `id`, `talent_code`, `job_title`, `full_name`, `email` للأدمن.
- Notice period موجود داخل `offer.notice_period_days` وليس داخل Talent block.
- `status_label` موجود بالفعل في الطلب والعنصر؛ ادعاء أن القائمة تعيد Raw status فقط غير صحيح مع الكود الحالي.

الناقص مقارنة بما ورد في التقرير:

- اسم العميل ووسيلة التواصل في `HiringRequestResource`.
- هاتف الموهبة و`seniority_level` و`years_of_experience` في Item resource.
- `rejected_by` و`rejected_at` ومرحلة الرفض كحقول جاهزة للكارت. توجد Events تحتوي actor/time ويمكن الاشتقاق منها، لكن لا توجد View Model مباشرة.
- `availability` تم استبعاده سابقًا من الـbusiness flow لصالح `notice_period_days`، فلا ينبغي إعادته دون قرار جديد.

### حقول القوائم

**مؤكد جزئيًا:**

- `AdminTalentResource` لا يعيد `talent_code` رغم وجوده في Talent model.
- قائمة مقابلات تقييم المواهب لا تعيد Job role أوسنوات الخبرة داخل `InterviewResource`.
- Hiring Request وItems يعيدان `status_label` بالفعل.

### فلاتر Admin Help Requests

**موجودة وموثقة في OpenAPI الحالي.**

الكود والتوثيق يدعمان:

- `scope`
- `search`
- `status[]`
- `help_type[]`
- `assigned_admin_id`
- `created_from`
- `created_to`
- `per_page`

هذه النقطة في التقرير قديمة أوتم اختبارها قبل آخر Generate/Import.

### إنشاء Talent/Client أوReset Password من الأدمن

**غير موجود كإدارة حسابات عامة.**

- Admin accounts CRUD موجود.
- إنشاء Talent موجود كجزء من Start Hiring Process الخاص بـJob Form submission، وليس كEndpoint عامة.
- Reset Password الذاتي موجود في Auth flow، لكن لا يوجد Admin action لإعادة تعيين كلمة مرور مستخدم.

هذا كان مطلوبًا في التقرير أساسًا لتسهيل الاختبار/impersonation. يجب حسم هل هو Product Feature في Figma أمTest tooling؛ لا يفضل إضافة إنشاء حسابات أوImpersonation للإنتاج لمجرد تسهيل الاختبارات.

### Tax Card في Admin

**موجودة حاليًا.**

المسار:

`GET /api/v1/admin/clients/{client}/workspaces/{workspace}/tax-card`

وهو محمي بصلاحية عرض العملاء.

### GET Client Help Requests يعيد 404

**Flow ناقص ومؤكد.**

ملف Routes الخاص بالعميل يحتوي فقط على:

- `GET /api/v1/client/workspaces/{workspace}/help-requests/options`
- `POST /api/v1/client/workspaces/{workspace}/help-requests`

لا توجد List أوShow للعميل. إذا كانت شاشة العميل تعرض تاريخ طلباته أوتفاصيلها، فيجب إضافة List وShow مع Workspace scoping وصلاحية العضو.

## فجوات التوثيق الحالية

ملفات OpenAPI الموجودة لا تمثل الكود الحالي بالكامل:

- Client Workspace schema لا تعرض `cr_number` و`tax_card` رغم وجودهما في Request.
- Talent Hiring Offers تعرض Enum قديمة لحالات Items.
- Offer respond-on-behalf لا يعرض Enum للـdecision.
- الإصلاحات المحلية الخاصة بـ`conditional_required_fields` والـexpired slots لم تدخل التوثيق المولد بعد.
- Client Help Requests لا تظهر لأن List/Show غير موجودتين أصلًا.

بعد تثبيت الكود يجب تشغيل Generate داخل Docker ثم إعادة Import للمصادر الثلاثة في Apidog.

## حالة الاختبارات

تم تشغيل الحزمة كاملة داخل Docker project الذي يخدم `localhost:8000` باستخدام SQLite معزولة للاختبارات:

- `50/50` اختبارات ناجحة.
- `329` assertion ناجحة.
- الاختبارات المضافة تغطي تحديدًا: تعطل Calendar، انتهاء كل Slots، 404 قبل أول Application، منع الرد على عرض عنصر ملغي، ورد الأدمن على موعد مقابلة التوظيف بالنيابة.

يوجد Warning غير مؤثر بسبب عدم قدرة PHPUnit على كتابة `.phpunit.result.cache` داخل الحاوية. لا يؤثر ذلك على تنفيذ أوحصيلة الاختبارات.

## ترتيب العمل المقترح

### P0 — قبل أي Deploy

1. مراجعة التعديلات المحلية الحالية ثم Commit مستقل.
2. نشر الكود وتشغيل migrations إن وجدت ضمن بقية تغييرات الفرع (حزمة الإصلاح الحالية نفسها لا تضيف Migration).
3. توليد OpenAPI على السيرفر، ثم تحديث مصدر Apidog؛ التوليد المحلي تم والتحقق منه.

### P1 — إغلاق الـflows المؤكدة

1. جعل أسباب Hold وCancel إلزامية إذا تقرر قبول هذا الـbreaking validation للواجهات المرتبطة.
2. إضافة Client Help Requests List وShow إذا كانت الشاشة موجودة.
3. إكمال Company Workspace validation لـCR وTax Card إذا اعتمد كونهما إلزاميين؛ التوثيق الحالي أصبح يعرض الحقلين.
4. توثيق Help Request assign/close بشكل أوضح داخل Options.

### P2 — تحسين البيانات وتجربة الدمج

1. إضافة الحقول المطلوبة لقوائم Admin دون تحميل زائد.
2. إضافة `scope=active` للعروض أوتوثيق الفلتر التركيبي رسميًا.
3. تحسين Resume Parser عبر Retry/Fallback وAlias matching.
4. اعتماد شكل موحد لـ`available_actions` أوإضافة Action metadata مركزية حتى لا يخمن الفرونت المسارات.

## قرارات تحتاج اعتمادًا قبل التنفيذ

- هل يوجد Minimum lead time لاختيار Interview slot؟ وما قيمته؟
- هل `cr_number` و`tax_card` إلزاميان عندما `is_company=true`؟
- هل Admin account creation/reset/impersonation جزء من المنتج أمأدوات اختبار فقط؟
- هل قائمة Client Help Requests موجودة في التصميم النهائي؟
- هل كارت Rejection Details يحتاج أعمدة مباشرة أميمكن بناؤه من Events؟
- هل نضيف `scope=active` أميلتزم الفرونت بتركيبة `status[]` و`offer_status[]`؟

