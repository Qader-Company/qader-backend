# خطة Resume Parser وملء بيانات الموهبة تلقائيًا

الحالة: **تم التنفيذ باستخدام APILayer Resume Parser بالخطة المجانية**  
تاريخ المراجعة: 4 نوفمبر 2026  
النطاق: رفع الموهبة للسيرة الذاتية، تحليلها، وإرجاع اقتراحات منظمة للواجهة لاستخدامها في Auto Complete لحقول Onboarding الحالية.  
خارج النطاق: حفظ بيانات البروفايل تلقائيًا دون مراجعة الموهبة، إنشاء Master Data جديدة من نص السيرة الذاتية، أوتقييم جودة الموهبة.

## 1. المطلوب

إنشاء Endpoint للموهبة يستقبل ملف CV، يرسله إلى Resume Parser، ثم يرجع بيانات منظمة للFrontend حتى:

1. يعرض القيم المستخرجة كاقتراحات داخل الحقول.
2. تراجع الموهبة القيم وتعدلها قبل الحفظ.
3. يستخدم Frontend Endpoints الحفظ الحالية بعد موافقة الموهبة.
4. لا يكتب Backend بيانات الـParser مباشرة في جداول البروفايل.

## 2. نتيجة البحث عن الباكدج

### 2.1 الباكدج الأقرب للمطلوب

الباكدج المقصودة على الأرجح هي:

```text
sharpapi/laravel-resume-parser
```

المصادر:

- [GitHub — sharpapi/laravel-resume-parser](https://github.com/sharpapi/laravel-resume-parser)
- [Packagist — sharpapi/laravel-resume-parser](https://packagist.org/packages/sharpapi/laravel-resume-parser)
- [SharpAPI Resume/CV Parsing](https://sharpapi.com/en/catalog/ai/hr-tech/resume-cv-parsing)

ما تم التأكد منه:

- متوافقة مع PHP `^8.1` وLaravel `10/11/12/13`، وبالتالي متوافقة مع المشروع الحالي PHP 8.3 وLaravel 13.
- الإصدار الذي تمت مراجعته `v1.1.0`.
- تدعم PDF وDOC وDOCX وصيغًا أخرى وصور الـCV الممسوحة ضوئيًا.
- تدعم أكثر من 80 لغة حسب توثيق المزود.
- ترجع بيانات منظمة مثل الاسم، البريد، الهاتف، الخبرات، المهارات، التعليم، اللغات، الشهادات والروابط.
- تعتمد على إرسال الملف إلى SharpAPI؛ الباكدج ليست Parser محليًا داخل Laravel.
- العمل يتم كـAI Job: إرسال الملف أولًا، ثم Polling حتى اكتمال النتيجة.
- تحتاج `SHARP_API_KEY` واتصالًا خارجيًا فعالًا من السيرفر.
- الباكدج حديثة واستخدامها العام ما زال محدودًا، لذلك يجب عدم ربط Business Logic مباشرة بكلاساتها.

### 2.2 باكدج `resume-parser` القديمة في NPM

توجد باكدج Node باسم `resume-parser`، لكنها قديمة وغير نشطة منذ سنوات، ولا تناسب Laravel الحالي. لن تعتمدها الخطة.

### 2.3 بديل تمت مراجعته

[Affinda Resume Parser](https://resume-parser.affinda.com/docs/) يقدم Cloud API أوSelf-hosted Container، ويرجع JSON من Request واحدة، ويذكر أن Cloud Parser لا يحتفظ بنسخة الملف أوالنتيجة. لكنه لا يقدم Laravel package مماثلة؛ التكامل سيكون مباشرة باستخدام Laravel HTTP Client.

القرار الحالي:

- لن تعتمد SharpAPI لأنها خدمة مدفوعة، وقد أزيلت الباكدج وإعداداتها من المشروع.
- اعتمد APILayer Resume Parser كبديل Hosted لا يحتاج موديلًا محليًا، بخطة مجانية قدرها 100 طلب شهريًا وفق صفحة الخدمة وقت المراجعة.
- تظل طبقة `ResumeParserGatewayInterface` مستقلة حتى يمكن تغيير المزود دون تغيير Endpoint أوResponse الخاصة بالFrontend.
- يدعم المزود رفع ملفات PDF وDOC وDOCX، وتذكر وثائقه أن الملفات تحذف تلقائيًا بعد التحليل ولا تحفظ النتيجة.
- يجب اختبار جودة CV العربية عمليًا؛ توثيق المزود يذكر أن أفضل جودة للغة الإنجليزية مع دعم مقبول للغات أخرى.

## 3. الوضع الحالي في المشروع

### 3.1 رفع وحفظ الـCV

- الموهبة ترفع `cv` حاليًا ضمن خطوة `personal-information`.
- الملف يحفظ باستخدام Spatie Media Library في Collection باسم `cv` وعلى Private `local` disk.
- Collection تقبل PDF وDOC وDOCX وتحتفظ بملف واحد فقط لكل Talent.
- الحد الحالي للملف `10 MB`.
- تحميل الملف بعد الحفظ يتم من Endpoint محمية.

### 3.2 بيانات يمكن ملؤها من الـCV

الـProfile الحالي يدعم:

- الاسم الكامل.
- الهاتف وWhatsApp.
- الدولة والمدينة والجنسية.
- اللغات ومستوى اللغة.
- الملخص المهني.
- المسمى الوظيفي.
- مستوى الخبرة وعدد سنوات الخبرة.
- المهارات والأدوات والمجالات.
- LinkedIn وGitHub وBehance وPortfolio.
- الخبرات السابقة وتواريخها ومسؤولياتها.
- المشروعات وروابطها ووصفها.

### 3.3 بيانات يستخرجها المزود ولا يملك المشروع حقولًا لها حاليًا

- التعليم والمؤهلات.
- الدورات والشهادات.
- الجوائز والمنشورات والمراجع.
- Volunteer Experience.
- Work Authorization وDrivers Licenses.

هذه البيانات لن تنشئ لها الخطة جداول جديدة، ولن ترجع ضمن الـAuto Complete في النسخة الأولى إلا إذا اعتمدت إضافتها للنموذج لاحقًا.

## 4. التصميم المقترح للـAPI

### 4.1 Endpoint

المسار المقترح:

```http
POST /api/v1/talent/onboarding/resume/parse
```

الحماية:

- `auth:sanctum`
- Token خاص بـTalent Portal.
- `X-Authorization: {{api_key}}`
- `Accept: application/json`
- `Accept-Language: en|ar`
- Rate Limit خاص بالخدمة لأن كل تحليل له تكلفة خارجية.

### 4.2 Request

نوع الطلب: `multipart/form-data`

```text
cv: required file
```

Validation مطابق للنظام الحالي:

```php
cv => required|file|mimes:pdf,doc,docx|max:10240
```

لا يستقبل الـEndpoint أي User ID أوTalent ID من العميل؛ الموهبة تحدد من الـAccess Token.

### 4.3 السلوك الأساسي المقترح

1. يتحقق Backend من الملف والهوية وRate Limit.
2. يحتفظ بالملف مؤقتًا للتحليل فقط دون استبدال الـCV الحالية.
3. يرسل الملف من السيرفر إلى Resume Parser.
4. ينتظر نتيجة الـJob بحد زمني مضبوط.
5. يحول استجابة المزود إلى Response ثابتة تخص Qader.
6. يطابق النصوص المستخرجة مع Master Data الموجودة دون إنشاء Rows جديدة.
7. يرجع الاقتراحات للFrontend دون حفظ الملف أوأي حقل Profile.
8. يحذف الملف المؤقت بعد اكتمال التحليل، سواء نجحت العملية أوفشلت.
9. Frontend يعرض الاقتراحات، والموهبة تختار أوتعدل القيم.
10. عند حفظ `personal-information` ترفع الموهبة الـCV مرة أخرى من خلال الـFlow الحالي، وهناك فقط تحفظ في Media Library.

> قرار معتمد: Endpoint التحليل لا تحفظ CV. إعادة رفع الملف عند حفظ Personal Information مقصودة.

## 5. شكل Response المقترح

لا ترجع استجابة المزود الخام إلى Frontend. ترجع Contract ثابتة تخص Qader:

```json
{
  "success": true,
  "data": {
    "cv": {
      "file_name": "ahmed-wael-cv.pdf",
      "mime_type": "application/pdf",
      "size": 248123
    },
    "suggestions": {
      "personal_information": {
        "full_name": "Ahmed Wael",
        "detected_email": "ahmed@example.com",
        "phone_raw": "+20 100 000 0000",
        "residence_country": {
          "raw": "Egypt",
          "match": {
            "id": 1,
            "name": "Egypt"
          }
        },
        "residence_city": {
          "raw": "Cairo",
          "match": {
            "id": 10,
            "name": "Cairo"
          }
        },
        "languages": [
          {
            "raw": "English",
            "match": {
              "id": 2,
              "name": "English"
            },
            "proficiency_level": null
          }
        ]
      },
      "skills_and_experiences": {
        "professional_summary": "Backend developer with ...",
        "years_of_experience": 5,
        "job_title": {
          "raw": "Backend Developer",
          "match": {
            "id": 12,
            "name": "Backend Developer"
          }
        },
        "seniority_level": null,
        "linkedin_url": "https://linkedin.com/in/example",
        "github_url": "https://github.com/example",
        "portfolio_url": null,
        "skills": [
          {
            "raw": "Laravel",
            "match": {
              "id": 18,
              "name": "Laravel"
            }
          },
          {
            "raw": "Event Sourcing",
            "match": null
          }
        ],
        "tools": [],
        "experiences": [
          {
            "job_title": "Backend Developer",
            "company_name": "Example Company",
            "company_url": null,
            "started_at": "2022-01-01",
            "ended_at": null,
            "currently_working": true,
            "responsibilities": "Built and maintained APIs.",
            "display_order": 0
          }
        ],
        "projects": []
      }
    },
    "unmatched": {
      "job_titles": [],
      "skills": ["Event Sourcing"],
      "tools": [],
      "languages": []
    },
    "warnings": []
  },
  "message": "Resume parsed successfully. Review the suggested data before saving."
}
```

### 5.1 ملاحظات على البيانات

- `detected_email` للعرض فقط ولا يغير Email الحساب.
- `phone_raw` لا يقسم تلقائيًا إلى Country Code وNumber إلا بعد إضافة Parser موثوق لأرقام الهاتف أوتأكيد المستخدم.
- لا نخمن `seniority_level` إذا لم يوفر المزود قيمة يمكن تحويلها بثقة إلى Enum الحالية.
- لا نخمن Professional Field من Job Title دون قاعدة Mapping معتمدة.
- Master Data المطابقة ترجع كـ`match` تحتوي ID؛ غير المطابق يرجع `match: null` ولا ينشئ سجلًا جديدًا.
- Frontend يرسل فقط IDs التي اختارتها الموهبة عبر Endpoints الحفظ الحالية.

## 6. مطابقة Master Data

### 6.1 المطابقة الآمنة في النسخة الأولى

تتم المطابقة بالترتيب التالي:

1. إزالة المسافات الزائدة وتوحيد حالة الأحرف الإنجليزية.
2. Exact match مع الاسم بالإنجليزية.
3. Exact match مع الاسم بالعربية.
4. تجاهل العلامات البسيطة التي لا تغير المعنى عند الحاجة.

تشمل:

- Countries وCities.
- Languages.
- Job Titles.
- Skills.
- Tools.

### 6.2 ما لن يحدث تلقائيًا

- لا يوجد Fuzzy match يختار ID من نفسه عند تشابه الأسماء فقط.
- لا تنشأ Skill أوJob Title أوLanguage جديدة من الـCV.
- لا تربط Job Title بمجال غير مختار للموهبة.
- لا ترجع Master Data غير فعالة.

إذا كانت الموهبة اختارت `professional_field_id` بالفعل، تقيد نتائج Job Titles وSkills وTools بالبيانات التابعة لهذا المجال مثل بقية Onboarding.

## 7. الهيكل البرمجي المقترح

### 7.1 مزود التحليل

لا توجد باكدج Composer إضافية. يستخدم Adapter عميل HTTP الموجود في Laravel للاتصال بخدمة APILayer.

### 7.2 الملفات والمكونات

داخل Talent Module:

```text
Application/Contracts/ResumeParserGatewayInterface.php
Application/Data/ParsedResumeData.php
Application/Services/ParseTalentResumeService.php
Application/Services/ResumeAutocompleteMapper.php
Infrastructure/ResumeParsing/ApiLayerResumeParserGateway.php
Presentation/Http/Controllers/TalentResumeParserController.php
Presentation/Http/Requests/ParseTalentResumeRequest.php
Presentation/Http/Resources/ParsedResumeResource.php
```

المسؤوليات:

- `ResumeParserGatewayInterface`: يعزل التطبيق عن مزود التحليل.
- `ApiLayerResumeParserGateway`: يرفع الملف إلى APILayer ويحول الاستجابة إلى الشكل الداخلي ويعزل أخطاء المزود.
- `ResumeAutocompleteMapper`: يحول النتيجة الخام إلى حقول Qader ويطابق Master Data.
- `ParseTalentResumeService`: يدير الملف المؤقت، التحليل، وإتلاف الملف بعد انتهاء الطلب دون حفظه في Media Library.
- `ParsedResumeResource`: يثبت شكل Response للFrontend.

يسجل Interface Binding داخل `TalentModuleServiceProvider` حتى يمكن تغيير المزود دون تعديل Controller أوService.

## 8. الإعدادات المقترحة

يحتاج التشغيل إلى `APILAYER_RESUME_PARSER_KEY`. عنوان الخدمة والـTimeout قابلان للضبط من Environment، وإعدادات Rate Limit الداخلية الخاصة بالـEndpoint موجودة بالفعل.

## 9. Error Handling المقترح

| الحالة | HTTP | السلوك |
| --- | --- | --- |
| ملف غير صالح أوأكبر من 10MB | `422` | Validation errors على `cv` |
| ملف سليم لكن لا يمكن استخراج بيانات منه | `422` | رسالة واضحة بأن الـCV غير قابلة للتحليل |
| API Key غير مضبوطة | `503` | الخدمة غير مهيأة، دون كشف تفاصيل الإعدادات |
| انتهاء الحد المجاني | `503` | الخدمة غير متاحة مؤقتًا مع Log داخلي |
| Timeout أوعطل المزود | `503` | يحذف الملف المؤقت ويمكن للمستخدم إعادة المحاولة |
| Rate limit داخلي | `429` | منع الاستهلاك المتكرر |
| نجاح مع بيانات ناقصة | `200` | ترجع البيانات المتاحة مع `warnings` |

لا يسجل Log محتوى الـCV أوالاستجابة الخام أوPII. يسجل فقط معرف العملية عند توفره، مدة التنفيذ، الحالة، وكود الخطأ الآمن.

## 10. التنفيذ المتزامن مقابل Queue

### النسخة الأولى المقترحة

Endpoint واحدة متزامنة تحقق الطلب الحالي: ترفع الملف وترجع الاقتراحات في نفس الاستجابة.

هذا مناسب لأن الاستخدام فردي أثناء Onboarding، وليس Batch Import، ولأن توثيق الباكدج يذكر أن الـJob عادة يستغرق بضع ثوانٍ.

### متى نتحول إلى Async

إذا أظهرت التجربة أن زمن التحليل غير ثابت أويتجاوز Timeout، يصبح الشكل:

```http
POST /api/v1/talent/onboarding/resume/parse
GET  /api/v1/talent/onboarding/resume/parses/{parse_id}
```

النسخة Async تحتاج تخزين حالة آمن أوCache/Job tracking، لذلك لا تقترحها الخطة من البداية دون حاجة فعلية.

## 11. الخصوصية والأمان

المقصود بالخصوصية هنا أن APILayer خدمة خارج Qader. عند التحليل سيخرج ملف الـCV من سيرفر Qader ويرسل إلى سيرفرات APILayer، وقد يحتوي الاسم والبريد والهاتف والعنوان والخبرات وغيرها من البيانات الشخصية.

قبل Production يجب تنفيذ ومراجعة الآتي:

1. مراجعة شروط APILayer وسياسة الاحتفاظ والحذف للملفات والنتائج.
2. التأكد من المنطقة الجغرافية التي تعالج فيها الملفات إذا كان ذلك مطلوبًا تعاقديًا.
3. إضافة نص واضح للموهبة بأن الملف سيحلل بواسطة مزود خارجي، إذا تطلبت سياسة المنصة ذلك.
4. عدم تسجيل محتوى الـCV أوResponse الخام في Logs.
5. عدم حفظ الملف المؤقت محليًا بعد انتهاء Request.
6. التأكد تعاقديًا من عدم استخدام ملفات المواهب للتدريب أوأغراض غير تقديم الخدمة.

هذه ليست جداول أوFlow إضافية؛ هي مراجعة قانونية وتشغيلية لأننا نتعامل مع بيانات حقيقية لأشخاص. إذا لم تناسب شروط APILayer، يمكن تغيير المزود من خلال الـAdapter نفسه.

## 12. الاختبارات المطلوبة

تستخدم الاختبارات Fake لـ`ResumeParserGatewayInterface` ولا تتصل بالخدمة الحقيقية.

1. Talent مصادق عليها ترفع PDF وتحصل على Suggestions.
2. DOC وDOCX مقبولان، وباقي الصيغ الحالية مرفوضة.
3. الملف الأكبر من 10MB مرفوض.
4. الاسم والملخص والروابط والخبرات تتحول إلى شكل Qader الصحيح.
5. Skills وJob Titles المطابقة ترجع IDs الحالية.
6. النصوص غير المطابقة ترجع داخل `unmatched` دون إنشاء Master Data.
7. Master Data غير الفعالة لا تستخدم.
8. Job Titles وSkills تلتزم بالمجال المهني المختار عند وجوده.
9. فشل المزود لا يغير CV الحالية ويحذف الملف المؤقت.
10. نجاح التحليل لا يحفظ الملف في Media Library ويحذف الملف المؤقت بعد بناء Response.
11. لا تحفظ اقتراحات الـParser في Profile قبل إرسال الموهبة لطلب الحفظ المعتاد.
12. يترك اكتشاف لغة محتوى الـCV للمزود، ولا ترسل لغة التحليل من `Accept-Language`.
13. `Accept-Language` يحدد رسائل الـAPI ولغة أسماء Master Data المطابقة فقط.
14. Client أوAdmin token لا يستطيع استخدام Endpoint Talent.
15. Rate Limit يرجع `429` بعد الحد المعتمد.
16. توثيق OpenAPI يحتوي Multipart body وHeaders وأمثلة Success وValidation وService unavailable.

## 13. لا توجد تغييرات Database مطلوبة مبدئيًا

النسخة المتزامنة لا تحتاج Migration جديدة لأن:

- ملف التحليل مؤقت ولا يحفظه الـEndpoint.
- الاقتراحات ترجع للواجهة ولا تحفظ كبيانات مستقلة.
- الـCV والبيانات النهائية تحفظ لاحقًا عبر Endpoints Onboarding الحالية.

سنحتاج جدولًا جديدًا فقط إذا اعتمد Async Jobs دائمة، أوأردنا Audit كامل لكل Parse، أوأردنا الاحتفاظ بالنتائج الخام؛ وهذه أمور ليست مطلوبة حاليًا.

## 14. مراحل التنفيذ بعد الاعتماد

1. إنشاء حساب APILayer والاشتراك في Free Plan واستخراج API Key.
2. إضافة المفتاح إلى Environment على Local/Test دون حفظه في Git.
3. اختبار دقة ملفات CV عربية وإنجليزية حقيقية.
4. متابعة استهلاك الحد الشهري من Headers التي يرجعها المزود.
5. بناء Normalizer وMaster Data Matcher.
6. بناء Request وService وController وResource.
7. إضافة Route وRate Limiter.
8. إضافة الترجمات العربية والإنجليزية.
9. إضافة Endpoint Name وScribe examples وتحديث Talent OpenAPI.
10. إضافة Feature وUnit tests باستخدام Fake Gateway.
11. تجربة CV إنجليزية وعربية حقيقية في بيئة Local/Test قبل Production.
12. قياس زمن الاستجابة وجودة الحقول وتحديد الحاجة إلى Queue.

## 15. القرارات المعتمدة

1. **المزود الأساسي:** APILayer Resume Parser باستخدام Free Plan؛ SharpAPI غير معتمدة بسبب التكلفة.
2. **التصميم البرمجي:** يظل الـGateway مستقلًا حتى لا يتغير عقد الـAPI عند تغيير المزود لاحقًا.
3. **حفظ الملف:** Endpoint التحليل لا تحفظ الملف. يحذف بعد التحليل، ويرفعه Frontend مرة أخرى عند حفظ Personal Information.
4. **شكل التنفيذ:** Endpoint واحدة متزامنة في النسخة الأولى، والتحويل إلى Queue فقط إذا أثبت قياس الزمن الحاجة.
5. **لغة المحتوى:** يترك اكتشاف لغة الـCV للمزود. `Accept-Language` لا يفرض لغة التحليل، ويستخدم فقط لرسائل النظام وMaster Data المترجمة.
6. **الحقول غير الموجودة:** تجاهل Education وCertifications وأي حقول لا يملك Onboarding مكانًا لها حاليًا.
7. **الحفظ التلقائي:** لا تحفظ أي اقتراحات في Profile؛ الموهبة تراجعها ثم ترسلها عبر Endpoints الحفظ الحالية.
8. **قاعدة البيانات:** لا توجد Migration جديدة لهذا الجزء.

الـAPI والـMapping وAPILayer Adapter جاهزون. يحتاج التحليل الحقيقي فقط إلى وضع `APILAYER_RESUME_PARSER_KEY` صالح في Environment.
