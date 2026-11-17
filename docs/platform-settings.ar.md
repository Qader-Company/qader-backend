# إعدادات المنصة — Platform Settings

الحالة: **منفذ جزئيًا ومحدث حتى 17 نوفمبر 2026**
النطاق الحالي: الإعدادات العامة التي يستطيع الأدمن تغييرها وتؤثر على دورة التوظيف، الـMarketing Website، والأجزاء المشتركة الثابتة داخل البورتالات.  
ملاحظة: نُفذ موديول Platform Settings باستخدام `spatie/laravel-settings`، مع Endpoints مستقلة للمجموعات وصلاحيات وأحداث Audit.

> لا يشمل هذا الملف أي Master Data. الدول والمدن والعملات واللغات والمجالات والمهارات والأدوات وغيرها تظل في موديولاتها وجداولها الحالية.

> تقرأ دورة التوظيف الآن قيمها من `HiringSettings`، كما تقرأ حدود مقابلات تقييم الموهبة من `TalentEvaluationSettings`، وتقرأ دورة Trash مدة الاحتفاظ من `TrashSettings`.

### ملخص حالة التنفيذ

| الجزء | الحالة الحالية |
| --- | --- |
| تثبيت `spatie/laravel-settings` وجدول `settings` وSettings migrations | منفذ |
| Typed Settings classes وتسجيلها في `config/settings.php` | منفذ |
| Admin APIs منفصلة لكل Settings group | منفذ |
| صلاحيات العرض والتعديل وAudit للقيم القديمة والجديدة | منفذ |
| ربط مدد عروض ومقابلات التوظيف وحد عدم الرد بالفلو | منفذ |
| ربط حدود رفض وإعادة جدولة مقابلة تقييم Talent بالفلو | منفذ |
| ربط مدة Trash بالإزالة التلقائية واليدوية من صفحة المحذوفات | منفذ |
| استخدام صورة Talent المجهولة في Marketplace | منفذ حاليًا من Settings؛ تقرر تحويلها إلى Asset ثابت ويحتاج التنفيذ إلى تنظيف الربط الحالي |
| تخزين بيانات الدعم والروابط القانونية وFooter | منفذ من جهة Admin Settings |
| Public API تقرأ Website/Support/Legal settings للـMarketing Website | منفذ |
| إعدادات بيانات الشركة ونسبة برنامج الإحالة الظاهرة في Website Figma | منفذ عبر Settings migration مستقلة |
| استخدام أسعار Paid Services في شراء ودفع فعلي | غير منفذ؛ التخزين وإدارة الأدمن فقط منفذان |
| تخزين Hiring Support Requests العامة | منفذ كـRecords تشغيلية مستقلة؛ إدارة الأدمن لم تعتمد بعد |
| Marketing FAQs وPartners وTestimonials القابلة للزيادة | منفذة بجداول وAdmin CRUD وPublic read endpoints مستقلة |
| Job Forms العامة لكل وظيفة (`application-form` و`submissions`) | منفذة وتظل مستخدمة كما هي |
| فورم التقديم العامة المستقلة في Website Marketing | ملغاة؛ لا ينفذ لها Backend flow جديد |

## 1. الإعدادات المعتمدة

| المفتاح | النوع | القيمة الافتراضية | من يعدله | الاستخدام |
| --- | --- | ---: | --- | --- |
| `hiring.offer_response_duration_hours` | Positive integer | `48` ساعة | Admin مخول | مهلة رد Talent على كل نسخة عرض مرسلة |
| `hiring.post_interview_decision_duration_hours` | Positive integer | `24` ساعة | Admin مخول | مهلة قرار Client وTalent بعد اكتمال مقابلة التوظيف |
| `hiring.talent_offer_no_response_limit` | Positive integer | `2` مرات | Admin مخول | عدد مرات انتهاء عرض دون رد قبل تعطيل حساب Talent |
| `talent_evaluation.interview_slot_rejection_limit` | Non-negative integer | `2` مرات | Admin مخول | أقصى عدد لرفض Talent لكل المواعيد المقترحة في مقابلة تقييمه |
| `talent_evaluation.confirmed_interview_reschedule_limit` | Non-negative integer | `1` مرة | Admin مخول | أقصى عدد لإلغاء Talent موعد تقييم مؤكد وطلب مواعيد جديدة |
| `trash.retention_days` | Positive integer | `30` يومًا | Admin مخول | مدة ظهور العنصر في Trash قبل إخفائه منها تلقائيًا مع إبقاء سجله في قاعدة البيانات |
| `platform.support_response_business_days` | Positive integer | `1` يوم عمل | Admin مخول | المدة المعروضة للعميل لرد فريق Qader على Help Request |
| `platform.support_email` | Nullable email | `info@qadereg.com` | Admin مخول | بريد الدعم المستخدم في الـWebsite والبورتالات |
| `platform.support_phone` | Nullable string | `null` | Admin مخول | رقم التواصل العام |
| `platform.support_whatsapp` | Nullable string | `null` | Admin مخول | رقم WhatsApp العام |
| `platform.privacy_policy_url` | Nullable URL | `null` | Admin مخول | رابط سياسة الخصوصية المستخدم في التسجيل والنماذج |
| `platform.terms_and_conditions_url` | Nullable URL | `null` | Admin مخول | رابط الشروط والأحكام العام |
| `website.footer_copyright_text_ar` | String | `© {year} قادر. جميع الحقوق محفوظة.` | Admin مخول | نص حقوق النشر العربي في Footer الموقع |
| `website.footer_copyright_text_en` | String | `© {year} Qader. All rights reserved.` | Admin مخول | نص حقوق النشر الإنجليزي مع سنة ديناميكية |
| `website.company_founded_year` | Positive integer | `2022` | Admin مخول | سنة تأسيس Qader الظاهرة في About Qader |
| `website.referral_commission_percentage` | Percentage | `10` | Admin مخول | نسبة برنامج الإحالة الظاهرة في Home |
| `website.company_location_ar` | Nullable string | `6 أكتوبر، مصر` | Admin مخول | موقع الشركة المعروض بالعربية في About Qader |
| `website.company_location_en` | Nullable string | `6th of October, Egypt` | Admin مخول | موقع الشركة المعروض بالإنجليزية |
| `marketplace.anonymous_talent_image` | Nullable string | `null` | Admin مخول | منفذ حاليًا فقط؛ مقرر إلغاؤه لصالح Asset ثابت مع الحفاظ على Response الحالية |
| `paid_services.qader_expert_opinion_price` | Positive decimal | `250` | Admin مخول | سعر فتح Qader Expert Opinion للعميل |
| `paid_services.qader_expert_opinion_currency` | Currency code | `EGP` | Admin مخول | عملة خدمة Qader Expert Opinion |
| `paid_services.detailed_rejection_feedback_price` | Positive decimal | `100` | Admin مخول | سعر فتح Detailed Rejection Feedback للموهبة |
| `paid_services.detailed_rejection_feedback_currency` | Currency code | `EGP` | Admin مخول | عملة خدمة Detailed Rejection Feedback |
| `paid_services.profile_expert_review_price` | Positive decimal | `10` | Admin مخول | سعر Profile Expert Review للموهبة |
| `paid_services.profile_expert_review_currency` | Currency code | `USD` | Admin مخول | عملة خدمة Profile Expert Review |

### مصدر الإضافات من Figma

- Marketing Website يحتوي Footer مشتركًا، والنص الحالي في التصميم هو `© 2025 Qader. All rights reserved.`؛ سنستخدم `{year}` بدل تثبيت سنة قديمة.
- Client Portal يعرض أن فريق Qader يرد على Help Request خلال `1 business day`.
- Talent Portal يحيل المستخدم إلى Qader Support في حالات تقييد الحساب وعدم الرد، لذلك بيانات الدعم يجب ألا تظل نصوصًا ثابتة داخل الواجهة.
- روابط Privacy Policy وTerms تظهر كروابط عامة مشتركة، وليست Master Data.
- صورة Talent في Marketplace يجب أن تكون صورة افتراضية لأن العميل لا يرى هوية الموهبة قبل المرحلة المسموح بها.
- حد رفض مواعيد مقابلة التقييم الحالي مرتان، وطلب إعادة الجدولة بعد التأكيد مرة واحدة.
- الخدمات المدفوعة الثلاث الظاهرة في Figma معتمدة للتنفيذ المستقبلي، وأسعار التصميم هي القيم الافتراضية ويستطيع الأدمن تعديل السعر والعملة من Platform Settings.
- مدة ظهور المحذوفات في Trash قرار Business معتمد وليست Master Data؛ القيمة الافتراضية `30` يومًا ويمكن للأدمن تعديلها. انتهاء المدة لا يحذف السجل من قاعدة البيانات.
- صفحة Home في Website Marketing تعرض نسبة إحالة `10%`، وصفحة About Qader تعرض سنة التأسيس `2022` وموقع الشركة؛ هذه قيم مفردة قابلة للتعديل ومكانها `WebsiteSettings`.
- البريد المعتمد هو `info@qadereg.com`. لم يظهر في Figma الحالية رقم هاتف أوWhatsApp أوروابط Privacy وTerms؛ تظل هذه القيم `null` حتى تعتمد، ولا نخمنها.

القيم التي افتراضها `null` لم يظهر لها محتوى نهائي معتمد في Figma، لذلك لن نخزن بيانات وهمية. يجب تعبئتها قبل الإطلاق إذا كانت واجهة المستخدم ستعرضها.

> إعدادات Website الأربعة منفذة في `WebsiteSettings` وSettings migration وAdmin/Public APIs.

## 2. تفاصيل السلوك

### 2.1 `offer_response_duration_hours`

- عند إرسال نسخة عرض، يحسب:

```text
response_due_at = sent_at + offer_response_duration_hours
```

- الفلو يقرأ القيمة الآن من `HiringSettings` عبر `HiringRuntimeSettings`.
- تغيير الإعداد يؤثر على العروض التي ترسل بعد التغيير فقط، ولا يعيد حساب Deadline لعرض أرسل بالفعل.
- عند Resume بعد Hold يبدأ Deadline كامل جديد باستخدام القيمة الحالية وقت Resume.
- عند انتهاء المهلة دون رد:
  - تتحول نسخة العرض إلى `expired`.
  - يمنع Talent من الرد عليها.
  - يظل الأدمن قادرًا على تسجيل رد بالنيابة أوإرسال نسخة عرض جديدة.
  - يسجل `offer_response_deadline_expired` في `hiring_request_events`.

### 2.2 `hiring.post_interview_decision_duration_hours`

- يبدأ عند انتقال Hiring Interview إلى `completed`:

```text
decision_due_at = interview.completed_at + post_interview_decision_duration_hours
```

- القيمة الافتراضية `24` ساعة مطابقة لمدة اليوم الظاهرة في Figma.
- الفلو يقرأ القيمة الآن من `HiringSettings` عند إكمال المقابلة وحساب `decision_due_at`.
- Deadline يطبق على Client وTalent فقط.
- بعد انتهائه لا يستطيع الطرف العادي إرسال قراره، بينما يظل الأدمن المسؤول قادرًا على تنفيذ Action أوتسجيل القرار بالنيابة.
- تغيير الإعداد يؤثر على المقابلات التي تكتمل بعد التغيير فقط، ولا يعيد حساب `decision_due_at` بدأ بالفعل.
- عند Hold يتوقف Deadline الجاري، وعند Resume يبدأ Deadline كامل جديد بالقيمة الحالية.

### 2.3 `talent_offer_no_response_limit`

- القيمة الافتراضية `2`.
- خدمة انتهاء العروض تقرأ القيمة الآن من `HiringSettings`.
- كل عرض يصل إلى `response_due_at` وهو ما زال `pending` يزيد `talents.offer_no_response_count` مرة واحدة.
- معالجة الانتهاء يجب أن تكون Idempotent حتى لا يحتسب العرض نفسه أكثر من مرة.
- عندما يصل العداد إلى الحد:

```text
users.is_active = false
```

- يسجل Event لتعطيل الحساب مع العداد والحد المستخدم وقت التنفيذ.
- الأدمن لا يتقيد بتعطيل الحساب، ويستطيع تسجيل رد بالنيابة أوإدارة العرض.
- اعتبر العداد حاليًا تراكميًا ولا يصفر بعد رد Talent على عرض آخر، إلى أن يعتمد قرار مختلف.
- إعادة تفعيل الحساب بعد التعطيل تتم يدويًا بواسطة Admin مخول؛ تنفيذ الـAction المستقل يراجع ضمن موديول المواهب.

### 2.4 حدود مقابلة تقييم Talent

- `interview_slot_rejection_limit` مستخدم فعليًا في `TalentInterviewWorkflow` وفي `TalentInterviewResource`.
- `confirmed_interview_reschedule_limit` مستخدم فعليًا في Workflow وResource بدل الاعتماد على حد ثابت.
- القيم تطبق على محاولات جديدة بعد تعديل الإعداد، ولا تغير التاريخ المسجل لمحاولة انتهت بالفعل.
- القيمة `0` تعني تعطيل Action المقابل للطرف العادي، بينما يظل الأدمن قادرًا على إدارة المقابلة.

### 2.5 بيانات الدعم والقانونية

- تستخدم بيانات الدعم نفسها في Marketing Website وTalent Portal وClient Portal حتى لا تتكرر قيم مختلفة في أكثر من مكان.
- `support_response_business_days` يحدد النص المعروض للمستخدم فقط؛ قياس SLA فعليًا أوالتصعيد الآلي يحتاج موديول Support مستقل.
- روابط Privacy وTerms عامة للمنصة. الشروط الخاصة بنوع عقد أواتفاقية تظل داخل موديول العقود وليست هذه الإعدادات.
- لا يعرض رابط فارغ في الواجهة عند بقاء القيمة `null`.

### 2.6 Footer الموقع وصورة Talent المجهولة

- `footer_copyright_text_*` يقبل placeholder باسم `{year}` ويستبدل بالسنة الحالية عند الإرجاع للواجهة.
- التنفيذ الحالي يستخدم `marketplace.anonymous_talent_image` من Settings.
- القرار المعتمد الآن أن صورة Talent المجهولة Asset ثابت داخل المشروع، وليست قيمة يعدلها الأدمن. يلزم عند التنفيذ تحويل `TalentMarketplaceCardResource` إلى الـAsset الثابت وإخراج إعداد `anonymous_talent_image` من شاشة وإدارة Platform Settings، مع بقاء شكل حقل `image` في Response كما هو.

### 2.7 Website Marketing

- اختلاف Dark وLight مسؤولية Frontend ولا يحتاج Settings منفصلة أوAPI مختلفة.
- Job Forms المرتبطة بالوظائف المنشورة ليست ملغاة، وتظل Endpoints جلب نموذج الوظيفة وإرسال Submission مستخدمة كما هي.
- الملغي هو فورم التقديم العامة المستقلة الظاهرة في Website Marketing فقط؛ لا تنشأ لها جداول أوEndpoints جديدة، ولا يؤثر إلغاؤها على Job Forms الحالية.
- القيم المفردة القابلة للتعديل تحفظ في `WebsiteSettings`: Footer، سنة التأسيس، نسبة الإحالة، وموقع الشركة بالعربية والإنجليزية.
- بيانات الدعم والروابط القانونية لا تكرر داخل `WebsiteSettings`؛ يعاد استخدام مجموعة `platform` الحالية.
- يلزم Public read-only endpoint يجمع القيم الآمنة للعرض العام فقط، ولا يعرض باقي Platform Settings الداخلية.
- المقترح:

```text
GET /api/v1/public/website
```

- يطبق `Accept-Language` على القيم المترجمة، ويرجع Support وLegal وFooter وCompany facts اللازمة للواجهة.
- نموذج طلب دعم التوظيف المتكرر في Website لا يستخدم Help Requests الحالية؛ لأنها تتطلب Workspace وMember. ينفذ كموديول `HiringSupportRequests` مستقل مع Rate Limit وجدول `hiring_support_requests`.

## 3. اعتماد `spatie/laravel-settings`

### نتيجة البحث والتوافق

- الحزمة الرسمية: `spatie/laravel-settings`.
- الإصدار الحالي وقت المراجعة: `3.10`.
- يدعم PHP `^8.2` وLaravel/Illuminate `^12.0|^13.0`.
- المشروع يستخدم PHP `^8.3` وLaravel `13`، لذلك الحزمة متوافقة.
- الحزمة توفر Typed Settings classes، Database وRedis repositories، Settings migrations، caching، وLaravel container injection.
- الحزمة لا توفر Admin UI أوAPI أوصلاحيات Business أوAudit لمن عدل القيمة؛ هذه تظل مسؤولية موديولنا.

المراجع الرسمية:

- [Spatie Laravel Settings repository](https://github.com/spatie/laravel-settings)
- [Package composer requirements](https://github.com/spatie/laravel-settings/blob/main/composer.json)
- [Default package configuration](https://github.com/spatie/laravel-settings/blob/main/config/settings.php)
- [Default settings table migration](https://github.com/spatie/laravel-settings/blob/main/database/migrations/create_settings_table.php.stub)

### التثبيت داخل Docker

تم تثبيت الإصدار `3.10.2` وتسجيله في `composer.lock`. أمر التثبيت المرجعي:

```bash
docker compose -f compose.yaml -f compose.local.yaml exec app composer install
```

الإصدار المثبت يدعم إنشاء Settings class وSettings migration معًا:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan make:setting HiringSettings --group=hiring --migration
```

### التخزين الذي توفره الحزمة

لن ننشئ جدول `platform_settings` مخصصًا. سنستخدم جدول الحزمة الافتراضي `settings`:

| العمود | الغرض |
| --- | --- |
| `id` | معرف الصف |
| `group` | مجموعة الإعدادات، وهنا `hiring` |
| `name` | اسم الخاصية داخل المجموعة |
| `locked` | قفل الخاصية عند الحاجة |
| `payload` | القيمة بصيغة JSON |
| `created_at`, `updated_at` | أوقات الإنشاء والتحديث |

يوجد Unique index على `group + name`. الحزمة لا تحتوي `updated_by_admin_id`؛ لذلك Admin المنفذ والقيم القديمة والجديدة ستحفظ في Audit event خاص بالموديول عند استدعاء Update API.

### Settings classes المنفذة

```php
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HiringSettings extends Settings
{
    public int $offer_response_duration_hours;

    public int $post_interview_decision_duration_hours;

    public int $talent_offer_no_response_limit;

    public static function group(): string
    {
        return 'hiring';
    }
}
```

وبنفس الأسلوب تقسم باقي القيم بدون Class واحدة ضخمة:

```text
HiringSettings           => group: hiring
TalentEvaluationSettings => group: talent_evaluation
TrashSettings            => group: trash
PlatformSettings         => group: platform
WebsiteSettings          => group: website
MarketplaceSettings      => group: marketplace
PaidServicesSettings     => group: paid_services
```

تسجل هذه الـSettings classes صراحة داخل `config/settings.php`. سنفضل التسجيل الصريح بدل Auto-discovery لأن عددها محدود ولأن ذلك يجعل الاعتمادات واضحة.

### Settings migration المنفذة

ملف داخل `database/settings` يضيف القيم الافتراضية:

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('hiring.offer_response_duration_hours', 48);
        $this->migrator->add('hiring.post_interview_decision_duration_hours', 24);
        $this->migrator->add('hiring.talent_offer_no_response_limit', 2);

        $this->migrator->add('talent_evaluation.interview_slot_rejection_limit', 2);
        $this->migrator->add('talent_evaluation.confirmed_interview_reschedule_limit', 1);

        $this->migrator->add('trash.retention_days', 30);

        $this->migrator->add('platform.support_response_business_days', 1);
        $this->migrator->add('platform.support_email', null);
        $this->migrator->add('platform.support_phone', null);
        $this->migrator->add('platform.support_whatsapp', null);
        $this->migrator->add('platform.privacy_policy_url', null);
        $this->migrator->add('platform.terms_and_conditions_url', null);

        $this->migrator->add('website.footer_copyright_text_ar', null);
        $this->migrator->add(
            'website.footer_copyright_text_en',
            '© {year} Qader. All rights reserved.',
        );

        // Added by the follow-up Website Settings migration.
        // website.company_founded_year = 2022
        // website.referral_commission_percentage = 10.0
        // website.company_location_ar = 6 أكتوبر، مصر
        // website.company_location_en = 6th of October, Egypt
        // website.footer_copyright_text_ar = © {year} قادر. جميع الحقوق محفوظة.

        $this->migrator->add('marketplace.anonymous_talent_image', null);

        $this->migrator->add('paid_services.qader_expert_opinion_price', 250);
        $this->migrator->add('paid_services.qader_expert_opinion_currency', 'EGP');
        $this->migrator->add('paid_services.detailed_rejection_feedback_price', 100);
        $this->migrator->add('paid_services.detailed_rejection_feedback_currency', 'EGP');
        $this->migrator->add('paid_services.profile_expert_review_price', 10);
        $this->migrator->add('paid_services.profile_expert_review_currency', 'USD');
    }
};
```

Settings migrations تعمل مع `php artisan migrate` العادي.

### الاستخدام داخل الكود

تحقن Settings class في Service بدل قراءة `config('hiring...')` مباشرة:

```php
public function __construct(private readonly HiringSettings $settings) {}

$dueAt = $sentAt->copy()->addHours(
    $this->settings->offer_response_duration_hours,
);
```

والتحديث من Use Case الأدمن:

```php
$settings->offer_response_duration_hours = $data['offer_response_duration_hours'];
$settings->post_interview_decision_duration_hours = $data['post_interview_decision_duration_hours'];
$settings->talent_offer_no_response_limit = $data['talent_offer_no_response_limit'];
$settings->save();
```

### الـCache

- سنفعل Cache في Production باستخدام `SETTINGS_CACHE_ENABLED=true`.
- الحزمة تحدث الـCache تلقائيًا عند استدعاء `save()`.
- أمر التنظيف عند الحاجة:

```bash
php artisan settings:clear-cache
```

- لا يتم تعديل جدول `settings` مباشرة؛ كل تعديل يمر عبر `HiringSettings` حتى تظل القيم المحملة والـCache متزامنين.

### قواعد التحقق

| المفتاح | التحقق |
| --- | --- |
| `offer_response_duration_hours` | `integer`, أكبر من أويساوي `1` |
| `post_interview_decision_duration_hours` | `integer`, أكبر من أويساوي `1` |
| `talent_offer_no_response_limit` | `integer`, أكبر من أويساوي `1` |
| `interview_slot_rejection_limit` | `integer`, أكبر من أويساوي `0` |
| `confirmed_interview_reschedule_limit` | `integer`, أكبر من أويساوي `0` |
| `retention_days` | `integer`, أكبر من أويساوي `1` |
| `support_response_business_days` | `integer`, أكبر من أويساوي `1` |
| `support_email` | `nullable`, `email` |
| `support_phone`, `support_whatsapp` | `nullable`, `string`, `max:32` |
| `privacy_policy_url`, `terms_and_conditions_url` | `nullable`, `url`, `max:2048` |
| `footer_copyright_text_ar`, `footer_copyright_text_en` | `nullable`, `string`, `max:500` |
| `company_founded_year` | `integer`, من `1900` إلى السنة الحالية |
| `referral_commission_percentage` | `numeric`, من `0` إلى `100` |
| `company_location_ar`, `company_location_en` | `nullable`, `string`, `max:255` |
| `anonymous_talent_image` | `nullable`, Media path أوURL صالح حسب آلية الرفع المعتمدة |
| أسعار الخدمات المدفوعة | `numeric`, أكبر من `0` |
| عملات الخدمات المدفوعة | `string`, `size:3`، ويجب أن تطابق كود Currency فعالًا |

القيم الافتراضية تضاف من Settings migration. بعد ربط الموديول لن نعتمد على صف مفقود؛ غياب خاصية مطلوبة يعتبر خطأ إعداد Deployment بدل إخفائه بـFallback صامت.

## 4. صلاحيات وAPI المنفذة للموديول

الصلاحيات:

```text
admin.platform-settings.view
admin.platform-settings.update
```

Endpoints المنفذة:

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

Endpoint القراءة العامة المنفذة للـMarketing Website:

```text
GET /api/v1/public/website
```

مثال Body:

```json
{
  "offer_response_duration_hours": 48,
  "post_interview_decision_duration_hours": 24,
  "talent_offer_no_response_limit": 2
}
```

التحديث يسجل Admin المنفذ والقيم القديمة والجديدة في Audit event.

كل PATCH يحدث Group واحدة فقط حتى يظل الـBody واضحًا، ولا يستطيع تعديل إعدادات موديول آخر بالخطأ.

## 5. قيم معتمدة لكنها ليست Platform Settings

| القيمة | وضعها |
| --- | --- |
| مدة Hiring Interview | `60` دقيقة ثابتة في الفلو الحالي، وليست إعداد Admin حتى الآن |
| نافذة زر Join | من بداية الموعد `starts_at` إلى نهايته `ends_at`، وليست إعدادًا |
| `notice_period_days` | قيمة خاصة بكل نسخة Hiring Offer، مع Fallback من Talent profile، وليست إعداد منصة |
| `reapply_after_months` | جزء من قرار رفض Talent Application عند اختيار `allowed_after_period`، وليست قيمة عامة معتمدة |
| Workspace invitation expiry | لم تعد مطلوبة بعد إلغاء Invitation flow والاعتماد على إنشاء العضو وإرسال بيانات الحساب |

## 6. عناصر راجعناها وليست Platform Settings

| العنصر | مكانه الصحيح |
| --- | --- |
| Countries, Cities, Currencies, Languages, Industries, Professional Fields, Skills, Tools | Master Data modules الحالية، وخارج هذا الملف بالكامل |
| محتوى صفحات Home / Services / About Qader والنصوص والصور | Frontend ثابت حاليًا؛ لا يخزن كحقول كثيرة داخل Settings |
| Marketing FAQs | جدول وترجمات مستقلان إذا اعتمدت إدارة الأدمن؛ ليست Talent Review FAQs وليست Settings |
| Partners | جدول مستقل عند اعتماد الإضافة والحذف والترتيب من الأدمن |
| Testimonials | جدول وترجمات مستقلان عند اعتماد الإدارة من الأدمن |
| Hiring Support Requests | Records تشغيلية مستقلة عن Settings وعن Help Requests المرتبطة بالـWorkspace وعن Hiring Requests |
| الوظائف المنشورة ونماذج التقديم المرتبطة بكل وظيفة | تبقى في Job Forms الحالية، وتظل Endpoints `application-form` و`submissions` مستخدمة كما هي |
| فورم التقديم العامة الظاهرة داخل Careers في Website Marketing | ملغاة؛ لا تحتاج Endpoint أوTable جديدة |
| Review Process FAQ لكل Screen State | جداول Talent Review FAQs الحالية لأن لكل شاشة أسئلتها |
| Help Requests | Records تشغيلية يديرها Client وAdmin؛ الإعداد الوحيد هنا هو مدة الرد المعروضة |
| Notification records | Notifications module؛ نصوص القوالب تحتاج Notification Templates مستقلة إذا أراد الأدمن تعديلها |
| نصوص الأزرار والعناوين ورسائل الحالات | ملفات الترجمة في Backend/Frontend، وليست Settings |
| Navigation routes في Website والبورتالات | Frontend routing ثابت |
| Terms الخاصة بنوع عقد | Contract Type configuration وليست `platform.terms_and_conditions_url` العامة |
| بيانات إرسال البريد وFirebase وGoogle Calendar ومفاتيح الخدمات | Environment/Secrets، ولا تعرض أوتعدل من Platform Settings |

## 7. القرارات المعتمدة والمفتوحة

### 7.1 قرارات معتمدة

1. `offer_no_response_count` تراكمي حاليًا مدى حياة Talent، ولا يصفر بعد رد ناجح.
2. إعادة تفعيل حساب Talent بعد وصوله لحد عدم الرد Action يدوي من Admin. تنفيذ Action والصلاحية الخاصة به ما زالا مطلوبين إذا لم يكونا موجودين عند بدء التنفيذ.
3. بريد الدعم المعتمد `info@qadereg.com`. الهاتف وWhatsApp وروابط Privacy وTerms تظل `null` لأن قيمها غير موجودة في Figma الحالية ولم تعتمد خارجيًا.
4. صورة Talent المجهولة Asset ثابت داخل المشروع، ولا يديرها الأدمن كـSetting.
5. الأدمن يدير Marketing FAQs وPartners وTestimonials، لذلك تنفذ كجداول قابلة للإضافة والحذف والترتيب والتفعيل، وليست Arrays داخل Spatie Settings.

### 7.2 قرار Hiring Support Requests المعتمد

- طلب دعم التوظيف القادم من Website يتخزن كـRecord مستقل في `hiring_support_requests`.
- لا يتحول إلى Help Request أوHiring Request، ولا يرتبط بـWorkspace أوWorkspace Member.
- إنشاء Client أوWorkspace أوHiring Request لاحقًا يتم من الفلو المعتاد كسجلات منفصلة، ولا يغير سجل طلب الدعم الأصلي.
- طريقة إدارة الأدمن للطلبات وحالاتها وActions الخاصة بها لم تعتمد بعد؛ لا نفترض Assign أوStart أوNotes أوClose بدون تصميم أوقرار لاحق.

## 8. Website Marketing — الحالة والخطوات التالية

### 8.1 الموجود الذي سيعاد استخدامه

- قائمة الوظائف وتفاصيلها ونموذج كل وظيفة وإرسال التقديم منفذة وتظل مستخدمة كما هي:
  - `GET /api/v1/public/jobs`
  - `GET /api/v1/public/jobs/{job}`
  - `GET /api/v1/public/jobs/{job}/application-form`
  - `POST /api/v1/public/jobs/{job}/submissions`
- الملغي فقط هو فورم التقديم العامة المستقلة الظاهرة داخل صفحة Careers في Website Marketing؛ لذلك لا نحذف أو نغير Job Forms APIs الحالية، ولا ننشئ Backend flow جديدًا لهذه الفورم الملغاة.
- تسجيل Talent والـOnboarding منفذان، ويستخدمهما زر `قدّم طلبك كموهبة`.
- بيانات الدعم والروابط القانونية وFooter محفوظة في Settings، ويقرأ الموقع القيم العامة الآمنة عبر Public Website API.
- Dark وLight لا يحتاجان أي اختلاف في Backend payload.

### 8.2 Settings المنفذة

أضيفت إلى `WebsiteSettings` وSettings migration مستقلة بدون تعديل migration المطبقة سابقًا:

```text
website.company_founded_year
website.referral_commission_percentage
website.company_location_ar
website.company_location_en
```

وتم توسيع `PATCH /api/v1/admin/platform-settings/website` للتحقق منها وتحديثها، وتظهر ضمن `GET /api/v1/admin/platform-settings` مع Audit الحالي.

هذه Endpoint خاصة فقط بالقيم المفردة الموجودة في `WebsiteSettings`. لا تستخدم لإضافة أوتعديل أوحذف Marketing FAQs أوPartners أوTestimonials؛ لكل جدول منها Endpoints مستقلة كما هو موضح في القسم 8.5.

نفس Settings migration الجديدة تحدث القيمة الحالية:

```text
platform.support_email = info@qadereg.com
```

وتترك Phone وWhatsApp وPrivacy وTerms بقيمة `null` حتى تعتمد. صورة Talent المجهولة تتحول إلى Asset ثابت مع الحفاظ على حقل `image` في Marketplace responses.

### 8.3 Public Website API المنفذة

```text
GET /api/v1/public/website
```

ترجع فقط القيم الآمنة للزائر العام:

```text
support
legal
footer
company
referral
```

ولا تعيد Hiring أوTalent Evaluation أوTrash أوPaid Services settings الداخلية. تعتمد القيم المترجمة على `Accept-Language`.

### 8.4 Hiring Support Requests المنفذة

النموذج متكرر في Home وTalent Acquisition وDedicated Teams وDigital Projects وAbout Qader. يخزن في جدول مستقل باسم `hiring_support_requests` يحتوي الحد الأدنى التالي:

```text
name
email
phone_country_code
phone_number
country_id
description
timestamps
```

لا تضاف حقول `reference` أو`company_name` أو`service_type` أو`source_page` في النطاق الحالي.

`status` و`assigned_admin_id` وأي جدول Notes ليست ضمن الحد الأدنى المعتمد حاليًا؛ تضاف فقط بعد اعتماد شاشة وفلو إدارة الأدمن.

الـPublic API المنفذة:

```text
POST /api/v1/public/hiring-support-requests
```

وتطبق `POST` Rate Limit مستقلًا. Endpoints وحالات إدارة الأدمن لم تعتمد بعد، ولا تتضمن الخطة الحالية أي Endpoint للتحويل إلى Help Request أوHiring Request.

### 8.5 البيانات التي يمكن أن يزيد عددها

لا تخزن Arrays كبيرة داخل Spatie Settings. نُفذت كجداول مستقلة:

```text
marketing_faqs + translations
marketing_partners
marketing_testimonials + translations
```

ولا تمر إدارتها عبر:

```text
PATCH /api/v1/admin/platform-settings/website
```

بل تفصل إلى Resources مستقلة:

```text
/api/v1/admin/website/faqs
/api/v1/admin/website/partners
/api/v1/admin/website/testimonials
```

ويكون لكل Resource منها Endpoints القائمة والإضافة والتفاصيل والتعديل والحذف حسب احتياج الشاشة، بصلاحيات مستقلة. أما القراءة العامة فتكون منفصلة عن Settings أيضًا:

```text
GET /api/v1/public/website/faqs
GET /api/v1/public/website/partners
GET /api/v1/public/website/testimonials
```

تعيد الـPublic Endpoints العناصر المفعلة فقط بالترتيب المعتمد، وتطبق `Accept-Language` على FAQs وTestimonials. يظل `GET /api/v1/public/website` مخصصًا للقيم العامة المفردة مثل Support وLegal وFooter وCompany وReferral.

البيانات الأولية مأخوذة من Figma ومن النسخة المنشورة الحالية على `https://qadereg.com/ar` و`/en`:

#### Marketing FAQs

- توجد 5 أسئلة وإجابات كاملة بالعربية والإنجليزية.
- يخزن الـSeeder الترجمتين ويفعّل الأسئلة الخمسة. من أمثلتها:

```text
السؤال: ما الفرق بين خدمات قادر الثلاث؟
الإجابة: في استقطاب المواهب نساعدك في البحث والتقييم بينما تتولى شركتك التوظيف. وفي الفرق المخصصة نبني لك فريقًا في مصر مع بيئة عمل مجهزة ودعم تشغيلي. أما المشروعات الرقمية فنتولى تنفيذ وتسليم مشروع كامل وفق نطاق متفق عليه.
```

- الأسئلة الأخرى:

```text
هل توفر قادر كفاءات تقنية وغير تقنية؟
هل تشمل الفرق المخصصة مقرات عمل مجهزة؟
من المسؤول عن إدارة الفريق والمهام اليومية؟
كيف تحدد تكلفة الخدمة وموعد البدء؟
```

- الإجابات والترجمات الإنجليزية مأخوذة حرفيًا من Website المنشور، وليست نصوصًا مولدة.

#### Marketing Partners

- Figma تحتوي 5 شعارات شركاء كـImage assets بدون أسماء نصية معتمدة.
- صُدرت الصور من Nodes التالية وحفظت ضمن Assets الـSeeder بالترتيب:

```text
6139:91231
6139:91232
6139:91233
6139:91234
6139:91235
```

- يكون اسم الشريك والرابط Nullable إلى أن يعتمد اسمهما، بينما الصورة والترتيب والتفعيل مطلوبة.

#### Marketing Testimonials

توجد 4 Testimonials كاملة باللغتين قابلة للـSeed:

```text
name: Tamem Mohamed / تميم محمد
role: Jr. Back End Developer / مطوّر Back End مبتدئ
quote: I'm happy to be part of Qader. The team is supportive, communication is always smooth, and I really feel appreciated here.
```

- باقي الأسماء المنشورة: `Sara Ahmed / سارة أحمد` و`Omar Khaled / عمر خالد` و`Mariam Hassan / مريم حسن`، مع Roles وQuotes باللغتين.
- لا توجد صورة واحدة مرتبطة نصيًا بصاحب الشهادة؛ الـAvatar يظل Nullable ولا نخمن أي صورة من مجموعة الصور الظاهرة.

النصوص والصور التسويقية الأخرى تظل ثابتة في Frontend حاليًا؛ لا ينفذ CMS شامل بدون شاشات Admin وقرار صريح.

### 8.6 تعريف Inputs للـAdmin Frontend

كل `GET` خاص بإدارة Website في Admin Portal يعيد الحقول بصيغة Inputs جاهزة حتى يستطيع Frontend بناء Form العرض أوالتعديل بدون تخمين نوع كل حقل.

يشمل ذلك:

```text
GET /api/v1/admin/platform-settings
GET /api/v1/admin/website/faqs
GET /api/v1/admin/website/faqs/{faq}
GET /api/v1/admin/website/partners
GET /api/v1/admin/website/partners/{partner}
GET /api/v1/admin/website/testimonials
GET /api/v1/admin/website/testimonials/{testimonial}
```

الشكل الموحد والبسيط لكل Input:

```json
{
  "key": "question_ar",
  "value": "القيمة الحالية",
  "type": "textarea"
}
```

قيمة `value` تحافظ على نوعها الحقيقي:

- `text` و`textarea` و`url`: نص أو`null`.
- `number`: رقم أو`null`، وليس رقمًا داخل String.
- `checkbox`: قيمة Boolean (`true` أو`false`).
- `file`: رابط الملف الحالي أو`null`؛ وعند الإضافة أوالتعديل يرسل Frontend الملف بالطريقة المعتمدة للرفع.

لا ترجع Metadata إضافية مثل `label` أو`required` أو`nullable` أو`validation` في هذا النطاق. Backend Validation يظل المصدر النهائي، والواجهة تعتمد على `type` لاختيار Component المناسب.

الترجمات ترجع كمفاتيح مستقلة بدل كائن ترجمة أو`translatable` metadata، مثل:

```json
[
  {
    "key": "question_ar",
    "value": "السؤال بالعربية",
    "type": "textarea"
  },
  {
    "key": "question_en",
    "value": "Question in English",
    "type": "textarea"
  }
]
```

قواعد مكان الإرجاع:

- Endpoint التفاصيل ترجع `data.inputs` بالقيم الحالية للسجل.
- Endpoints القوائم ترجع لكل عنصر `id` و`inputs` فقط، بالإضافة إلى `meta` الخاصة بالـPagination.
- `GET /api/v1/admin/platform-settings` يرجع كل مجموعة وبداخلها `inputs` فقط، فلا تتكرر القيمة خارج وداخل `inputs`.
- Responses الخاصة بالإنشاء أوالتعديل تعيد السجل بنفس شكل GET، بما فيه `inputs`، لتوحيد التعامل في Frontend.

أنواع Inputs المعتمدة للواجهة:

```text
text
textarea
number
email
tel
url
file
checkbox
```

تعريف الحقول لكل Resource:

| Resource | Inputs |
| --- | --- |
| Website Settings | `footer_copyright_text_ar` و`footer_copyright_text_en` من نوع `textarea`، `company_founded_year` من نوع `number`، `referral_commission_percentage` من نوع `number`، `company_location_ar` و`company_location_en` من نوع `text` |
| Marketing FAQ | `question_ar` و`question_en` و`answer_ar` و`answer_en` من نوع `textarea`، `display_order` من نوع `number`، `is_active` من نوع `checkbox` |
| Partner | `name` من نوع `text` وNullable، `logo` من نوع `file`، `url` من نوع `url` وNullable، `display_order` من نوع `number`، `is_active` من نوع `checkbox` |
| Testimonial | `name_ar` و`name_en` و`role_ar` و`role_en` من نوع `text`، `quote_ar` و`quote_en` من نوع `textarea`، `avatar` من نوع `file` وNullable، `display_order` من نوع `number`، `is_active` من نوع `checkbox` |

### 8.7 أمثلة Admin GET Responses

#### Website Settings

المثال التالي يوضح قيمة `data.website` داخل Response:

```json
{
  "inputs": [
    {
      "key": "footer_copyright_text_ar",
      "value": "© 2026 قادر. جميع الحقوق محفوظة.",
      "type": "textarea"
    },
    {
      "key": "footer_copyright_text_en",
      "value": "© 2026 Qader. All rights reserved.",
      "type": "textarea"
    },
    {
      "key": "company_founded_year",
      "value": 2022,
      "type": "number"
    },
    {
      "key": "referral_commission_percentage",
      "value": 10,
      "type": "number"
    },
    {
      "key": "company_location_ar",
      "value": "6 أكتوبر، مصر",
      "type": "text"
    },
    {
      "key": "company_location_en",
      "value": "6th of October, Egypt",
      "type": "text"
    }
  ]
}
```

#### FAQ List

```json
{
  "success": true,
  "data": {
    "faqs": [
      {
        "id": 1,
        "inputs": [
          {"key": "question_ar", "value": "ما الفرق بين خدمات قادر الثلاث؟", "type": "textarea"},
          {"key": "question_en", "value": "What's the difference between Qader's three services?", "type": "textarea"},
          {"key": "answer_ar", "value": "في استقطاب المواهب نساعدك في البحث والتقييم.", "type": "textarea"},
          {"key": "answer_en", "value": "Talent Acquisition: we source and assess candidates while your company does the hiring.", "type": "textarea"},
          {"key": "display_order", "value": 1, "type": "number"},
          {"key": "is_active", "value": true, "type": "checkbox"}
        ]
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    }
  }
}
```

`GET /api/v1/admin/website/faqs/{faq}` يعيد نفس كائن العنصر السابق مباشرة داخل `data` بدون `faqs` أو`meta`.

#### Partners List

```json
{
  "success": true,
  "data": {
    "partners": [
      {
        "id": 1,
        "inputs": [
          {"key": "name", "value": "Qader Partner", "type": "text"},
          {"key": "logo", "value": "https://api.example.com/storage/partners/partner.png", "type": "file"},
          {"key": "url", "value": "https://example.com", "type": "url"},
          {"key": "display_order", "value": 1, "type": "number"},
          {"key": "is_active", "value": true, "type": "checkbox"}
        ]
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    }
  }
}
```

`GET /api/v1/admin/website/partners/{partner}` يعيد نفس كائن العنصر السابق مباشرة داخل `data` بدون `partners` أو`meta`.

#### Testimonials List

```json
{
  "success": true,
  "data": {
    "testimonials": [
      {
        "id": 1,
        "inputs": [
          {"key": "name_ar", "value": "تميم محمد", "type": "text"},
          {"key": "name_en", "value": "Tamem Mohamed", "type": "text"},
          {"key": "role_ar", "value": "مطوّر Back End مبتدئ", "type": "text"},
          {"key": "role_en", "value": "Jr. Back End Developer", "type": "text"},
          {"key": "quote_ar", "value": "سعيد جدًا بكوني جزءًا من قادر.", "type": "textarea"},
          {"key": "quote_en", "value": "I'm happy to be part of Qader.", "type": "textarea"},
          {"key": "avatar", "value": null, "type": "file"},
          {"key": "display_order", "value": 1, "type": "number"},
          {"key": "is_active", "value": true, "type": "checkbox"}
        ]
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    }
  }
}
```

`GET /api/v1/admin/website/testimonials/{testimonial}` يعيد نفس كائن العنصر السابق مباشرة داخل `data` بدون `testimonials` أو`meta`.


## 9. سياسة الحذف وصفحة المحذوفات (منفذة)

> الخطة التفصيلية المبدئية نُقلت إلى `docs/sprints/trash-management-plan.ar.md` لأنها موديول Admin/Client مستقل وليست جزءًا من Platform Settings. يحتفظ هذا القسم فقط بملخص القرارات المعتمدة.

قرار صاحب المشروع (11 نوفمبر 2026):

- **أي حذف أولي ينفذه المستخدم على عنصر أعمال قابل للإدارة يكون Soft Delete.** يسمح بإزالة العنصر نهائيًا من واجهة Trash بصلاحية مستقلة، وينفذ النظام الإزالة تلقائيًا بعد مدة الاحتفاظ إذا لم توجد بيانات مرتبطة مانعة. الإزالة لا تحذف Row من قاعدة البيانات؛ يظل السجل محفوظًا للتاريخ والعلاقات. لا يشمل ذلك الحذف التقني الداخلي مثل Tokens وPivot rows والبيانات المؤقتة.
- **كل عملية حذف تحتاج صلاحية مستقلة** للأدمن، ولا تدخل ضمن صلاحيات العرض أو الإدارة العامة.
- **الوصول إلى صفحة المحذوفات نفسها بصلاحية** (`admin.trash.view`)، والأدمن بدونها لا يرى الصفحة ويأخذ `403`.
- تُعرض العناصر المحذوفة في **صفحة محذوفات واحدة عند الأدمن، مقسّمة إلى Tabs حسب نوع العنصر** (العملاء، ومساحات العمل، وأعضاء الفريق، وغيرها).
- Admin Trash لا تعرض Hiring Requests أوWorkspaces. Client Trash تعرض Team وRoles وWorkspaces؛ Team وRoles يتغيران حسب الـWorkspace النشطة، بينما Workspaces Trash مشتركة على مستوى حساب Client.
- Workspace المحذوفة لا تظهر في قائمة اختيار الـWorkspaces العادية، وتظهر فقط في Client Trash حتى استعادتها. يجب أن يظل الوصول للـTrash متاحًا إذا لم توجد Workspace نشطة.
- **نُفذ Backend الصفحات** في موديول Trash مستقل يخدم Admin وClient Portal، مع Soft Delete وAudit وRestore وRemove from Trash وجدولة الإزالة التلقائية.

### 9.1 متطلبات كل موديول قبل الصفحة

- Model فيه `SoftDeletes`، ولا يظهر العنصر المحذوف في القوائم والتفاصيل العادية.
- تسجيل `deleted_by` (الأدمن) و`deletion_reason` عندما يطلب التصميم سبباً، إما في سجل الأحداث الخاص بالموديول أو في أعمدة على الجدول.
- عدم كسر العلاقات: الـForeign keys تبقى، والتاريخ المرتبط (طلبات توظيف، عقود، أحداث) لا يُحذف.

### 9.2 الصفحة المنفذة

| الجزء | المقترح |
| --- | --- |
| الصلاحيات | `admin.trash.view` للعرض، و`admin.trash.restore` للاستعادة، و`admin.trash.remove` للإزالة النهائية من Trash، بالإضافة إلى صلاحية Soft Delete الخاصة بكل موديول |
| Admin Tabs المعتمدة | Jobs، Forms، Talents، Admins، Roles، Help Requests، Clients، Master Data |
| Client Tabs المعتمدة | Team، Roles، Workspaces؛ الأعمدة والأكشنز الأساسية ظاهرة في Figma |
| الأعمدة | الاسم أو المرجع، والحالة المعروضة للقراءة فقط، ومن حذف، وتاريخ الحذف، وموعد الإزالة التلقائية من Trash، والسبب، وActions (Restore / Remove Permanently) |
| الاستعادة | تعيد العنصر بالحالة نفسها وتُسجَّل كحدث؛ لا توجد استعادة لـHiring Requests لأنها غير قابلة للحذف |
| API | Endpoints منفصلة لكل Tab/موديول تحت `/api/v1/admin/trash/*`؛ لا يوجد `{type}` عام أو`trash/types` |

### 9.3 مدة الاحتفاظ والإزالة النهائية من Trash

- القيمة الافتراضية `trash.retention_days = 30`، ويمكن للأدمن تعديلها.
- تعديل القيمة يؤثر على كل العناصر التي ما زالت ظاهرة داخل Trash؛ يحسب `scheduled_removal_at` من `deleted_at` والمدة الحالية، وليس من Snapshot قديم للقيمة.
- عند تقليل المدة، يخفي الـScheduled Job العناصر التي أصبحت مستحقة في تشغيله التالي بعد إعادة فحص العلاقات. وعند زيادتها تمتد مدة ظهور العناصر الحالية.
- Remove from Trash اليدوي يحتاج صلاحية مستقلة، والتلقائي ينفذه Scheduled Job.
- في الحالتين يعاد فحص العلاقات المانعة؛ العنصر المرتبط لا يزال ظاهرًا في Trash.
- التنفيذ يضبط `removed_from_trash_at` فقط مع Audit event، ولا ينفذ `DELETE` أو`forceDelete()` على Row قاعدة البيانات.
- بيانات الصفوف الظاهرة في Figma مثل عدد Users أوWorkspaces والحالات هي Dummy Data، ولا تلغي قواعد التحقق في Backend.
- الـActivation Toggle داخل Trash يعرض الحالة الأصلية فقط ولا يغيرها.
- Restore مفترض كـAction متاح لكل الأنواع حسب الصلاحية.

### 9.4 الحذف الحالي بعد التنفيذ

- أدوار الأدمن وأدوار الـWorkspace وJob Forms وForm Submissions والأنواع المعتمدة في الخطة تستخدم Soft Delete.
- Talent Review FAQs ليست ضمن Tabs Trash المعتمدة حاليًا، لذلك لم تُضم إلى هذا النطاق.
- Admin Trash لا تشمل Hiring Requests أوWorkspaces، وClient Trash هي المسؤولة عن Workspaces.
