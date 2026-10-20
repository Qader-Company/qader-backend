# توثيق الـAPI محليًا باستخدام Scribe

Scribe ضمن `require-dev`، مع تعطيل الاكتشاف التلقائي وتسجيله فقط عندما تكون `APP_ENV=local`. أوامر الباكدج وراوتس التوثيق لا تُسجّل في الإنتاج أو staging أو الاختبارات.

## تنظيم البورتالات والموديولات

كل تشغيل لـ`scribe:generate` يُنتج:

- كوليكشن Postman واحدة بثلاثة مجلدات: `talent` و`client` و`admin`.
- مجلدات فرعية حسب الموديول، مثل `auth` و`profile` و`onboarding` و`master-data` و`countries` و`access-control`.
- ثلاثة ملفات OpenAPI مستقلة، كل ملف يضم endpoints دوره وطلب logout المشترك.
- صفحة HTML تعرض نفس تقسيم البورتالات والموديولات.

مسارات المصادقة التي تحتوي على `{type}` تُفرد في التوثيق حسب الأدوار المسموح بها في قيد الراوت. مثلًا تسجيل الدخول يظهر لكل دور، لكن تسجيل حساب أدمن لا يُضاف لأنه غير متاح في الراوت الأصلي. هذا التوسيع خاص بالتوثيق ولا يضيف أو يغير راوتس التطبيق.

التنظيم يُستنتج من مسار الراوت، فيستمر تلقائيًا عند إضافة endpoints إلى الموديولات. تحديد المصادقة يعتمد على middleware `auth:sanctum`. وصف الحقول والردود والأمثلة يُستكمل مع كل موديول.

## الروابط المحلية

بعد توليد الملفات وتشغيل التطبيق على بورت 8000:

| الاستخدام | الرابط |
| --- | --- |
| صفحة التوثيق | `http://localhost:8000/docs` |
| كوليكشن Postman بالأدوار الثلاثة | `http://localhost:8000/docs.postman` |
| Talent OpenAPI | `http://localhost:8000/docs/talent.openapi` |
| Client OpenAPI | `http://localhost:8000/docs/client.openapi` |
| Admin OpenAPI | `http://localhost:8000/docs/admin.openapi` |
| OpenAPI مجمّع للاطلاع | `http://localhost:8000/docs.openapi` |

استخدم الملفات الثلاثة المنفصلة مع Apidog؛ الملف المجمّع يعرض logout مرة واحدة فقط تحت `talent/auth`، بينما كل ملف منفصل يضم logout في `auth` الخاص بدوره.

تُحفظ الملفات داخل القرص المحلي في `storage/app/private/scribe/` بأسماء `talent.openapi.yaml` و`client.openapi.yaml` و`admin.openapi.yaml` و`collection.json`. عند التشغيل داخل Docker، الملفات تكون داخل volume التخزين الخاص بالحاوية.

## التوليد

مع PHP المحلي، بعد `composer install` ووجود `APP_ENV=local`:

```bash
php8.4 artisan scribe:generate
```

على الجهاز الحالي نستخدم `php8.4` لأن PHP الافتراضي يفتقد إضافات XML وDOM. Composer يستهدف PHP 8.3 للتوافق مع نسخة Docker.

مع Docker المحلي، بعد تغيير الكود أو الاعتماديات أعد بناء صورة التطبيق لأنها تنسخ الملفات وقت البناء، ثم شغّل التوليد داخل الحاوية:

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml up -d --build app nginx queue scheduler
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec -T app php artisan scribe:generate
```

ملف `compose.local.yaml` يفعّل `INSTALL_DEV_DEPENDENCIES=true` للصورة المحلية ويشارك ملفات CSS وJS الخاصة بالتوثيق بين التطبيق وNginx. البناء الافتراضي للإنتاج يستبعد حزم التطوير.

## إعداد Apidog مرة واحدة

1. افتح المشروع في تطبيق Apidog لسطح المكتب.
2. انتقل إلى `Settings → Import Data → Scheduled Import (Bind Data Sources)`.
3. أنشئ ثلاثة مصادر بصيغة `OpenAPI` باستخدام روابط الأدوار الثلاثة أعلاه.
4. اختر نفس Module المشروع للمصادر، واسمح للمواصفة بتحديد المجلدات؛ الملفات تحمل `x-apidog-folder` بالقيم مثل `talent/auth` و`admin/countries`.
5. اختر `Local Client` وفترة المزامنة المناسبة.
6. في خيارات التعارض اختر `Merge` أو `Overwrite Selected` للاحتفاظ بالتعديلات اليدوية. فعّل `Update endpoint folder` لنقل endpoints الموجودة إلى المجلدات الجديدة بدل إبقائها في الفولدر القديم.

لو استوردت الملف القديم، قد تبقى مسارات المصادقة العامة التي تحتوي على `{type}` لأنها تختلف عن المسارات المفردة الجديدة. راجعها واحذف النسخ القديمة بعد التحقق من الاستيراد الجديد. لا تفعّل حذف الموارد غير الموجودة على مستوى المشروع كله لكل مصدر، لأن كل مصدر يحتوي دورًا واحدًا فقط.

يلزم أن يكون السيرفر المحلي والتطبيق والمشروع مفتوحين، وأن تكون لديك صلاحية الكتابة. المزامنة دورية وليست فورية عند حفظ الكود؛ يجب تشغيل `scribe:generate` لتحديث المواصفات. لا يوجد مراقب ملفات تلقائي ضمن هذا الإعداد.

لم يتم تعديل حساب Apidog أو إعداد مصادره من داخل المشروع.

المراجع: [Scheduled Import](https://docs.apidog.com/scheduled-import-bind-data-sources-633932m0)، [Import Options](https://docs.apidog.com/en/import-options-633930m0)، [مجلدات Apidog](https://docs.apidog.com/x-apidog-folder-1981658m0).

## استخراج الطلبات والأمثلة

- استخراج التسجيل يستخدم FormRequest الخاص بالدور مباشرة دون تشغيل التسجيل أو التحقق من الطلب.
- تحديث المدينة يستخدم موديلًا توضيحيًا غير محفوظ أثناء استخراج القواعد.
- استخراج قواعد تحديث الأدمن وبروفايل الموهبة يستخدم وضع `DB::pretend` حتى لا يحتاج بيانات فعلية أو مستخدمًا مسجلًا. لا يتغير سلوك هذه الطلبات أثناء تشغيل الـAPI.
- حقول البروفايل وسياسات الحقول المتغيرة حسب التخصص تحتاج مراجعة مع الموديول؛ التوثيق العام لا يمثل كل إعداد تخصص أو مستخدم.
- استدعاءات الـAPI التلقائية معطلة. أضف أمثلة الردود عبر `@response` أو `@responseFile`.
- استبدل `{{api_key}}` في `X-Authorization` بالمفتاح المحلي، واضبط Bearer Token المناسب للطلب؛ مسارات التحقق والتحديث تحتاج أنواع التوكن المناسبة.

## الإنتاج

استخدم `composer install --no-dev --optimize-autoloader` مع `APP_ENV=production`، وابنِ كاش الإعدادات والراوتس في بيئة النشر نفسها. لا تنقل كاش البيئة المحلية أو ملفات التوثيق المولّدة إلى السيرفر. ملفات التوثيق مستبعدة من Git ومن سياق بناء Docker، وملف الإعداد قابل للتحميل بدون تثبيت Scribe.
