# إعدادات المنصة — Platform Settings

الحالة: **مرجع القرارات المعتمدة حتى 9 نوفمبر 2026**
النطاق الحالي: الإعدادات العامة التي يستطيع الأدمن تغييرها وتؤثر على دورة التوظيف، الـMarketing Website، والأجزاء المشتركة الثابتة داخل البورتالات.  
ملاحظة: هذا الملف يوثق الإعدادات فقط؛ موديول Platform Settings وEndpoints الخاصة به لم تنفذ بعد. عند التنفيذ سنستخدم مكتبة `spatie/laravel-settings` بدل بناء Repository وجدول Settings مخصصين.

> لا يشمل هذا الملف أي Master Data. الدول والمدن والعملات واللغات والمجالات والمهارات والأدوات وغيرها تظل في موديولاتها وجداولها الحالية.

> قرار التنفيذ المرحلي: لا ينتظر S06 تنفيذ موديول Platform Settings. سيستخدم مؤقتًا قيم `config` الافتراضية `48` ساعة لمهلة العرض، و`24` ساعة لمهلة قرار ما بعد المقابلة، و`2` لحد عدم الرد. تعزل قراءة القيم خلف Service أوSettings contract حتى نستبدل المصدر لاحقًا بـ`spatie/laravel-settings` دون تغيير Business workflow أوContracts الـAPI.

## 1. الإعدادات المعتمدة

| المفتاح | النوع | القيمة الافتراضية | من يعدله | الاستخدام |
| --- | --- | ---: | --- | --- |
| `hiring.offer_response_duration_hours` | Positive integer | `48` ساعة | Admin مخول | مهلة رد Talent على كل نسخة عرض مرسلة |
| `hiring.post_interview_decision_duration_hours` | Positive integer | `24` ساعة | Admin مخول | مهلة قرار Client وTalent بعد اكتمال مقابلة التوظيف |
| `hiring.talent_offer_no_response_limit` | Positive integer | `2` مرات | Admin مخول | عدد مرات انتهاء عرض دون رد قبل تعطيل حساب Talent |
| `talent_evaluation.interview_slot_rejection_limit` | Non-negative integer | `2` مرات | Admin مخول | أقصى عدد لرفض Talent لكل المواعيد المقترحة في مقابلة تقييمه |
| `talent_evaluation.confirmed_interview_reschedule_limit` | Non-negative integer | `1` مرة | Admin مخول | أقصى عدد لإلغاء Talent موعد تقييم مؤكد وطلب مواعيد جديدة |
| `platform.support_response_business_days` | Positive integer | `1` يوم عمل | Admin مخول | المدة المعروضة للعميل لرد فريق Qader على Help Request |
| `platform.support_email` | Nullable email | `null` | Admin مخول | بريد الدعم المستخدم في الـWebsite والبورتالات |
| `platform.support_phone` | Nullable string | `null` | Admin مخول | رقم التواصل العام |
| `platform.support_whatsapp` | Nullable string | `null` | Admin مخول | رقم WhatsApp العام |
| `platform.privacy_policy_url` | Nullable URL | `null` | Admin مخول | رابط سياسة الخصوصية المستخدم في التسجيل والنماذج |
| `platform.terms_and_conditions_url` | Nullable URL | `null` | Admin مخول | رابط الشروط والأحكام العام |
| `website.footer_copyright_text_ar` | Nullable string | `null` | Admin مخول | نص حقوق النشر العربي في Footer الموقع |
| `website.footer_copyright_text_en` | String | `© {year} Qader. All rights reserved.` | Admin مخول | نص حقوق النشر الإنجليزي مع سنة ديناميكية |
| `marketplace.anonymous_talent_image` | Nullable string | `null` | Admin مخول | صورة Talent الافتراضية الظاهرة للعميل بدل الصورة الحقيقية |
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

القيم التي افتراضها `null` لم يظهر لها محتوى نهائي معتمد في Figma، لذلك لن نخزن بيانات وهمية. يجب تعبئتها قبل الإطلاق إذا كانت واجهة المستخدم ستعرضها.

## 2. تفاصيل السلوك

### 2.1 `offer_response_duration_hours`

- عند إرسال نسخة عرض، يحسب:

```text
response_due_at = sent_at + offer_response_duration_hours
```

- القيمة الحالية في الكود `48` ساعة من `config/hiring.php` كـFallback حتى تنفيذ موديول Platform Settings.
- أثناء تنفيذ S06 تظل `config/hiring.php` هي المصدر المؤقت المعتمد لهذه القيمة.
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
- أثناء تنفيذ S06 تقرأ القيمة من `config` إلى أن ينفذ موديول Settings.
- Deadline يطبق على Client وTalent فقط.
- بعد انتهائه لا يستطيع الطرف العادي إرسال قراره، بينما يظل الأدمن المسؤول قادرًا على تنفيذ Action أوتسجيل القرار بالنيابة.
- تغيير الإعداد يؤثر على المقابلات التي تكتمل بعد التغيير فقط، ولا يعيد حساب `decision_due_at` بدأ بالفعل.
- عند Hold يتوقف Deadline الجاري، وعند Resume يبدأ Deadline كامل جديد بالقيمة الحالية.

### 2.3 `talent_offer_no_response_limit`

- القيمة الافتراضية `2`.
- أثناء تنفيذ S06 تقرأ القيمة من `config` إلى أن ينفذ موديول Settings.
- كل عرض يصل إلى `response_due_at` وهو ما زال `pending` يزيد `talents.offer_no_response_count` مرة واحدة.
- معالجة الانتهاء يجب أن تكون Idempotent حتى لا يحتسب العرض نفسه أكثر من مرة.
- عندما يصل العداد إلى الحد:

```text
users.is_active = false
```

- يسجل Event لتعطيل الحساب مع العداد والحد المستخدم وقت التنفيذ.
- الأدمن لا يتقيد بتعطيل الحساب، ويستطيع تسجيل رد بالنيابة أوإدارة العرض.
- اعتبر العداد حاليًا تراكميًا ولا يصفر بعد رد Talent على عرض آخر، إلى أن يعتمد قرار مختلف.
- طريقة إعادة تفعيل الحساب بعد التعطيل لم تعتمد بعد.

### 2.4 حدود مقابلة تقييم Talent

- `interview_slot_rejection_limit` يحل محل الثابت الحالي `Interview::MAX_SLOT_REJECTIONS` عند تنفيذ الموديول.
- `confirmed_interview_reschedule_limit` يحل محل منطق الفرصة الواحدة المبني حاليًا على `talent_reschedule_used_at`.
- القيم تطبق على محاولات جديدة بعد تعديل الإعداد، ولا تغير التاريخ المسجل لمحاولة انتهت بالفعل.
- القيمة `0` تعني تعطيل Action المقابل للطرف العادي، بينما يظل الأدمن قادرًا على إدارة المقابلة.

### 2.5 بيانات الدعم والقانونية

- تستخدم بيانات الدعم نفسها في Marketing Website وTalent Portal وClient Portal حتى لا تتكرر قيم مختلفة في أكثر من مكان.
- `support_response_business_days` يحدد النص المعروض للمستخدم فقط؛ قياس SLA فعليًا أوالتصعيد الآلي يحتاج موديول Support مستقل.
- روابط Privacy وTerms عامة للمنصة. الشروط الخاصة بنوع عقد أواتفاقية تظل داخل موديول العقود وليست هذه الإعدادات.
- لا يعرض رابط فارغ في الواجهة عند بقاء القيمة `null`.

### 2.6 Footer الموقع وصورة Talent المجهولة

- `footer_copyright_text_*` يقبل placeholder باسم `{year}` ويستبدل بالسنة الحالية عند الإرجاع للواجهة.
- `marketplace.anonymous_talent_image` يحفظ مسار Media أوURL للصورة الافتراضية، ويستخدم دائمًا في Marketplace بدل Media الشخصية للموهبة.
- غياب صورة مخصصة يجعل الـBackend يرجع الـasset الافتراضي الموجود في التطبيق؛ لا يرجع صورة Talent الحقيقية كـFallback.

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

### التثبيت المخطط داخل Docker

لن ينفذ الآن؛ عند بدء الموديول تكون الخطوات:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app composer require spatie/laravel-settings:^3.10

docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="config"

docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"
```

الإصدار `3.10` يدعم إنشاء Settings class وSettings migration معًا:

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

### Settings classes المقترحة

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
PlatformSettings         => group: platform
WebsiteSettings          => group: website
MarketplaceSettings      => group: marketplace
PaidServicesSettings     => group: paid_services
```

تسجل هذه الـSettings classes صراحة داخل `config/settings.php`. سنفضل التسجيل الصريح بدل Auto-discovery لأن عددها محدود ولأن ذلك يجعل الاعتمادات واضحة.

### Settings migration المقترحة

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
| `support_response_business_days` | `integer`, أكبر من أويساوي `1` |
| `support_email` | `nullable`, `email` |
| `support_phone`, `support_whatsapp` | `nullable`, `string`, `max:32` |
| `privacy_policy_url`, `terms_and_conditions_url` | `nullable`, `url`, `max:2048` |
| `footer_copyright_text_ar`, `footer_copyright_text_en` | `nullable`, `string`, `max:500` |
| `anonymous_talent_image` | `nullable`, Media path أوURL صالح حسب آلية الرفع المعتمدة |
| أسعار الخدمات المدفوعة | `numeric`, أكبر من `0` |
| عملات الخدمات المدفوعة | `string`, `size:3`، ويجب أن تطابق كود Currency فعالًا |

القيم الافتراضية تضاف من Settings migration. بعد ربط الموديول لن نعتمد على صف مفقود؛ غياب خاصية مطلوبة يعتبر خطأ إعداد Deployment بدل إخفائه بـFallback صامت.

## 4. صلاحيات وAPI مقترحة للموديول

الصلاحيات:

```text
admin.platform-settings.view
admin.platform-settings.update
```

Endpoints المقترحة:

```text
GET   /api/v1/admin/platform-settings
PATCH /api/v1/admin/platform-settings/hiring
PATCH /api/v1/admin/platform-settings/talent-evaluation
PATCH /api/v1/admin/platform-settings/platform
PATCH /api/v1/admin/platform-settings/website
PATCH /api/v1/admin/platform-settings/marketplace
PATCH /api/v1/admin/platform-settings/paid-services
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
| محتوى صفحات Home / Services / About Qader والنصوص والصور والمشروعات والشهادات | Website Content/CMS مستقل عند الحاجة لتعديله من الأدمن؛ لا يخزن كحقول كثيرة داخل Settings |
| الوظائف المنشورة ونماذج التقديم | Job Forms، لأنها بيانات تشغيلية وليست إعداد منصة |
| Review Process FAQ لكل Screen State | جداول Talent Review FAQs الحالية لأن لكل شاشة أسئلتها |
| Help Requests | Records تشغيلية يديرها Client وAdmin؛ الإعداد الوحيد هنا هو مدة الرد المعروضة |
| Notification records | Notifications module؛ نصوص القوالب تحتاج Notification Templates مستقلة إذا أراد الأدمن تعديلها |
| نصوص الأزرار والعناوين ورسائل الحالات | ملفات الترجمة في Backend/Frontend، وليست Settings |
| Navigation routes في Website والبورتالات | Frontend routing ثابت |
| Terms الخاصة بنوع عقد | Contract Type configuration وليست `platform.terms_and_conditions_url` العامة |
| بيانات إرسال البريد وFirebase وGoogle Calendar ومفاتيح الخدمات | Environment/Secrets، ولا تعرض أوتعدل من Platform Settings |

## 7. قرارات ما زالت مفتوحة

1. هل `offer_no_response_count` تراكمي مدى حياة Talent أم يحتسب مرات متتالية ويصفر بعد رد ناجح؟ الخطة الحالية تفترض أنه تراكمي.
2. كيف يعاد تفعيل حساب Talent بعد وصوله للحد: Action يدوي من Admin، أم سياسة زمنية؟
3. القيم النهائية لبريد ورقم وWhatsApp الدعم وروابط Privacy وTerms لم تعتمد بعد.
4. هل صورة Talent الافتراضية ترفع من شاشة Platform Settings، أم تظل Asset ثابتًا في المشروع مع إرجاع URL فقط؟

