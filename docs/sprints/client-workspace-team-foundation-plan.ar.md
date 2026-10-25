# تأسيس Client Portal — مساحات العمل والفريق والصلاحيات

الحالة: **معتمدة وتم تنفيذها**  
آخر مراجعة: 25 أكتوبر 2026  
النطاق: إنشاء واختيار مساحة العمل، تأسيس لوحة العميل، وإدارة أدوار وأعضاء كل مساحة مع عزل كامل بينها.

## 1. المرجع والهدف

تصميم Figma هو المرجع الأساسي. راجعت قسم `client portal` في Node `3802:39313`، وأهم الشاشات المرتبطة بالنطاق:

| الجزء | Node | النتيجة |
| --- | --- | --- |
| إنشاء أول Workspace | `4664:59963`, `3166:18282`, `4287:55632`, `4287:55913` | ترحيب، نموذج البيانات، رفع الشعار، وحالة النجاح |
| اختيار Workspace | `4664:60940`, `4671:69404`, `3174:19565` | الحساب يستطيع امتلاك أو الانضمام لأكثر من مساحة وإنشاء مساحة أخرى |
| Dashboard جديدة | `3170:19110` | العدادات صفر مع توجيه لبدء بناء الفريق أو طلب مساعدة قادر |
| Dashboard فعالة | `3200:16645` | أعضاء الفريق وطلبات التوظيف والعقود والمشاريع والبيانات الأخيرة |
| إدارة الأدوار | `4178:54523`, `4178:54634`, `4178:54811` | قائمة الأدوار، التفعيل، الإنشاء بالصلاحيات، والحذف بتأكيد |

الهدف النهائي لهذا التأسيس: يستطيع Client إنشاء أكثر من Workspace، الدخول إلى واحدة منها، إضافة موظفين، إنشاء أدوار محلية واختيار صلاحياتها، وإسناد أكثر من دور للعضو دون وصول إلى بيانات مساحة أخرى.

## 2. ملاحظات على Figma

1. شاشة `Team Members` تعرض فعليًا جدول Roles، رغم اسم الشاشة.
2. عمود `Type of` في جدول الأدوار غير موضح، وتعرض بيانات المثال نفس اسم الدور في عمودي `Roles` و`Type of`. لن نضيف له عمودًا في قاعدة البيانات حتى يحدد التصميم معنى فعليًا مختلفًا له.
3. Modal إنشاء Role منسوخ من Admin Portal؛ الـsidebar والمجموعات تخص الأدمن، وأسماء الصلاحيات ما زالت `Permission 1`. نعتمد شكل اختيار الصلاحيات فقط، بينما تأتي المجموعات والقيم من `ClientPermissionEnum`.
4. Modal حذف Role يعرض اسم شخص `omar ali` بدل اسم Role، كما أن خلفيته تعرض `Admins Management`. الـAPI سيحذف Role بالـID ويعيد اسمه الصحيح في الـresponse كي تستخدمه الواجهة في رسالة التأكيد.
5. لا توجد شاشة واضحة لقائمة الموظفين أو دعوتهم أو تعديل عضويتهم وإسناد أدوارهم. سننفذ Backend الخاص بالأعضاء والدعوات بناءً على الـflow المعتمد، مع عقد API مباشر حتى تضاف هذه الشاشات.
6. اختيار `Are you a company?` يعرض `Yes` في الاختيارين. نعتمدها boolean بقيمتي `Yes/No` لأن النص التعريفي يوضح أن المساحة قد تمثل شركة أو مشروعًا.
7. حقل Website ظاهر كقائمة وبـplaceholder هو `Select Country`. نعتمده حقل URL نصيًا اختياريًا وفق اسمه ووظيفته.
8. قيم Workspace Size المتكررة في التصميم placeholders؛ القيم المعتمدة موضحة أدناه.

## 3. القرارات المعتمدة

1. `Industry` هي Master Data مستقلة عن Professional Fields، ويشير `workspaces.industry_id` إلى `industries.id`.
2. العضو يستطيع حمل أكثر من Role داخل نفس Workspace.
3. منشئ المساحة يصبح عضوًا فيها ويحصل تلقائيًا على دور محمي مفتاحه `owner`.
4. `owner` يعامل مثل `super_admin` ولكن داخل Workspace واحدة فقط: يمتلك كل صلاحيات Client الحالية والمستقبلية، ولا يعدل أو يعطل أو يحذف أو يسند يدويًا.
5. إضافة الموظف تنشئ عضويته مباشرة وترسل بيانات الحساب إلى بريده؛ لا توجد دورة دعوات أو قبول منفصلة.
6. إذا كان البريد يخص حساب Client موجودًا، تضاف العضوية للحساب نفسه ويرسل له إشعار. إذا لم يوجد، ينشئ النظام حساب Client بكلمة مرور مؤقتة ويلزمه بتغييرها عند أول دخول.
7. قيم حجم المساحة: `1-10`, `11-50`, `51-200`, `201-500`, `500+`.
8. نستخدم جداول Workspace مستقلة للأدوار بدل تفعيل Spatie Teams على النظام كله، حتى لا نغير سلوك Admin RBAC المنفذ.
9. كل عمليات البيانات داخل البورتال تكون محددة صراحة بالمساحة في الرابط: `/api/v1/client/workspaces/{workspace}/...`.
10. اختيار Workspace في الواجهة لا يحتاج تخزين `current_workspace_id` أو إصدار Token جديد؛ الواجهة تحفظ الاختيار وترسل ID في مسار كل طلب.
11. حقل `Type of` الظاهر في جدول الأدوار لا ينشئ عمودًا في `workspace_roles` لأن التصميم لا يحدد معنى مستقلًا له.
12. Website يحفظ في `website_url` كعنوان URL اختياري، والاختيار الثاني في `is_company` يعامل كقيمة `No`.
13. حالة عضو المساحة تكون `active` أو `suspended` فقط، وعند إزالته يستخدم Soft Delete.
14. منصة رابط التواصل تختار من `WorkspaceSocialPlatformEnum` ولا ترسل كنص حر.

## 4. الموجود حاليًا

### جاهز للبناء عليه

- Auth كامل لتسجيل Client وتسجيل الدخول والتحقق من البريد وتحديث FCM token.
- `users` مع `type=client`.
- `clients` كسجل يمثل الشخص المستخدم للبوابة، وليس الشركة.
- `permissions` وPermission Registry وفصل الصلاحيات حسب Portal.
- CRUD أدوار الأدمن كمرجع لنفس تجربة العرض والـvalidation.
- Countries وCities وMedia.
- Client OpenAPI export موجود، لكنه لا يحتوي Routes للموديولات حتى الآن.

### غير موجود

- جداول ونماذج وخدمات Workspaces.
- Master Data للصناعات وإدارتها من الأدمن.
- اختيار مساحة والدخول إليها.
- Client Dashboard endpoints.
- Client permissions؛ `ClientPermissionEnum` فارغ حاليًا.
- أدوار وأعضاء ودعوات Workspace.
- أي Routes فعلية داخل قسم `client` في `config/modules.php`.

## 5. الجداول المقترحة لهذا التأسيس

### `workspaces`

| العمود | النوع | إلزامي | الملاحظات |
| --- | --- | --- | --- |
| `id` | bigint | نعم | المفتاح الأساسي |
| `owner_client_id` | bigint | نعم | منشئ المساحة؛ `clients.id` |
| `name` | varchar(255) | نعم | Workspace Name |
| `industry_id` | bigint | نعم | FK إلى `industries.id` |
| `description` | text | نعم | Business Description |
| `is_company` | boolean | نعم | Yes/No من التصميم |
| `country_id` | bigint | نعم | `countries.id` |
| `city_id` | bigint | لا | `cities.id` ويجب أن تتبع الدولة |
| `size_band` | varchar(16) | نعم | القيم الخمس المعتمدة |
| `website_url` | varchar(2048) | لا | URL صالح |
| timestamps | timestamps | — | — |

الشعار يحفظ في جدول `media` الحالي داخل collection خاصة بالـWorkspace، فلا نضيف عمود مسار للملف.

لن نضيف حاليًا `slug`, `commercial_registration_number`, `timezone`, `default_currency_id`, `status` أوSoft Deletes لأنها غير مطلوبة في التصميم المعتمد لهذه المرحلة.

### `industries` و`industry_translations`

Master Data جديدة ومنفصلة عن `professional_fields`:

- `industries`: `id`, `slug`, `is_active`, timestamps.
- `industry_translations`: `id`, `industry_id`, `locale`, `name`, timestamps، مع تفرد `(industry_id, locale)`.

تدار من Admin Portal وتعود للعميل مترجمة حسب `Accept-Language`. المساحة لا تقبل Industry غير فعالة عند الإنشاء أو التعديل، مع الاحتفاظ بالقيمة القديمة للمساحات الموجودة إذا عطّلها الأدمن لاحقًا.

### `workspace_social_links`

`id`, `workspace_id`, `platform`, `url`, timestamps. الروابط اختيارية ومتعددة، وترتيبها حسب `id` دون عمود ترتيب إضافي.

`platform` يخزن قيمة من `WorkspaceSocialPlatformEnum`:

`linkedin`, `facebook`, `instagram`, `x`, `youtube`, `tiktok`, `snapchat`, `threads`, `pinterest`, `reddit`, `telegram`, `whatsapp`, `discord`, `github`, `gitlab`, `behance`, `dribbble`, `medium`, `stack_overflow`, `twitch`, `other`.

وجود `other` يسمح بإضافة رابط لمنصة جديدة قبل إصدار Backend جديد، مع بقاء الاختيار نفسه Enum وليس نصًا حرًا.

### `workspace_members`

`id`, `workspace_id`, `client_id`, `status`, `joined_at`, `deleted_at`, timestamps، مع تفرد `(workspace_id, client_id)`.

الحالات المعتمدة: `active`, `suspended`. الإزالة تستخدم `deleted_at` مع Soft Delete للحفاظ على العقود والعمليات التي أنشأها العضو.

### `workspace_roles`

`id`, `workspace_id`, `key`, `name_en`, `name_ar`, `is_active`, timestamps، مع تفرد `(workspace_id, key)`.

دور `owner` ينشأ داخل transaction إنشاء المساحة، ويكون محميًا مثل `super_admin`.

### `workspace_role_permissions`

Pivot بين `workspace_roles` و`permissions`. لا يقبل إلا صلاحيات `portal=client`.

### `workspace_member_roles`

Pivot متعدد بين `workspace_members` و`workspace_roles`. يجب التحقق أن العضو والدور تابعان لنفس Workspace.

## 6. الصلاحيات الأولية

تضاف إلى `ClientPermissionEnum` وتظهر مجمعة للواجهة:

- Workspace: `client.workspace.view`, `client.workspace.update`.
- Dashboard: `client.dashboard.view`.
- Members: `client.members.view`, `client.members.create`, `client.members.manage`.
- Roles: `client.roles.view`, `client.roles.manage`.

إنشاء Workspace وقائمة المساحات إجراءات على مستوى حساب Client ولا تحتاج Role داخل مساحة لم تُنشأ بعد. صلاحيات الموديولات التالية تضاف إلى الـenum مع سبرنت كل موديول، ويتعامل `owner` معها تلقائيًا.

## 7. الـAPI المقترحة

### Industry Master Data

- `GET /api/v1/admin/industries`
- `POST /api/v1/admin/industries`
- `PATCH /api/v1/admin/industries/{industry}`
- `GET /api/v1/client/master-data/industries`

تضاف صلاحيتا `admin.industries.view` و`admin.industries.manage` إلى `AdminPermissionEnum`.

### Workspaces

- `GET /api/v1/client/workspaces/options` لإرجاع قيم `size_bands` و`social_platforms` للاختيارات في الواجهة.
- `GET /api/v1/client/workspaces`
- `POST /api/v1/client/workspaces` بصيغة `multipart/form-data`
- `GET /api/v1/client/workspaces/{workspace}`
- `PATCH /api/v1/client/workspaces/{workspace}` بصيغة `multipart/form-data`
- `GET /api/v1/client/workspaces/{workspace}/dashboard`

### Roles and permissions

- `GET /api/v1/client/access-control/workspaces/{workspace}/permissions`
- `GET /api/v1/client/access-control/workspaces/{workspace}/roles`
- `GET /api/v1/client/access-control/workspaces/{workspace}/roles/{role}`
- `POST /api/v1/client/access-control/workspaces/{workspace}/roles`
- `PATCH /api/v1/client/access-control/workspaces/{workspace}/roles/{role}`
- `PATCH /api/v1/client/access-control/workspaces/{workspace}/roles/{role}/status`
- `DELETE /api/v1/client/access-control/workspaces/{workspace}/roles/{role}`

### Members

- `GET /api/v1/client/workspaces/{workspace}/members`
- `POST /api/v1/client/workspaces/{workspace}/members` لإنشاء الحساب والعضوية وإرسال بيانات الدخول
- `GET /api/v1/client/workspaces/{workspace}/members/{member}`
- `PATCH /api/v1/client/workspaces/{workspace}/members/{member}` لتحديث الأدوار والحالة المسموحة
- `DELETE /api/v1/client/workspaces/{workspace}/members/{member}` لإزالة العضوية منطقيًا

كل endpoints الموثقة تستخدم `X-Authorization: {{api_key}}`, و`Accept: application/json`، وBearer `{{access_token}}` حيث يلزم.

## 8. قواعد الحماية الأساسية

1. التحقق من عضوية المستخدم الفعالة في Workspace قبل أي عملية.
2. كل Role وMember في route nested يجب أن يتبع Workspace الموجودة في الرابط.
3. لا يجوز لمستخدم تعديل عضويته بما يسحب آخر صلاحية إدارة أو إزالة الـowner.
4. `owner` لا يظهر ضمن الاختيارات القابلة للإسناد، ولا يقبل عبر Role IDs من الطلب.
5. الدور غير الفعال لا يسند لأعضاء أو دعوات جديدة.
6. لا يحذف Role مسندة إلى عضو.
7. إنشاء حساب الموظف والعضوية وإسناد الأدوار يتم داخل transaction واحدة، ثم يرسل البريد بعد نجاحها.
8. Logo يقبل PNG/JPG/JPEG/WEBP وبحد أقصى 5 MB طبقًا للتصميم.
9. `city_id` يجب أن يتبع `country_id`.
10. كل استعلامات Dashboard والفريق مستقبلًا تبدأ من `workspace_id` ولا تعتمد على `owner_client_id` وحده.

## 9. تقسيم التنفيذ المقترح

### الجزء الأول — Workspace Foundation

- إنشاء `industries` وترجماتها وAdmin CRUD وClient lookup endpoint.
- الجداول والنماذج والعلاقات.
- إنشاء Workspace مع owner member وowner role داخل transaction.
- قائمة المساحات، التفاصيل، التعديل، ورفع الشعار والروابط.
- Dashboard جديدة وفعالة بعقود response ثابتة، مع أصفار للموديولات غير المنفذة.

### الجزء الثاني — Workspace Access Control

- Client Permission enum والمزامنة.
- CRUD الأدوار المحلية وحمايات `owner`.
- Permission resolver خاص بالـWorkspace.

### الجزء الثالث — Team Members

- إنشاء حساب الموظف وربطه مباشرة بالمساحة مع Roles متعددة وإرسال بيانات الدخول بالبريد.
- قائمة الأعضاء وتعديل الأدوار والحالة والإزالة.

### الجزء الرابع — التوثيق والتسليم

- Client OpenAPI منظم إلى `workspaces`, `dashboard`, `roles`, `members`.
- أمثلة JSON وForm Data كاملة وثابتة لـApidog.
- Seeder آمن وقابل لإعادة التشغيل لمساحة تجريبية، owner، roles وأعضاء إذا اعتمد قبل التنفيذ.
- ملف تنفيذ عربي يسجل الجداول والـflows والـendpoints وخطوات التحديث على السيرفر.

## 10. نتيجة المراجعة النهائية

تم حسم قرارات إنشاء حساب الموظف مباشرة وحالات العضوية ومنصات التواصل. لا يوجد تعارض يمنع تنفيذ الـBackend.

الـFrontend لا يملك حتى الآن شاشات واضحة لإدارة الموظفين والدعوات؛ الـAPI المقترحة تغطي الـflow المعتمد إلى أن يحدث التصميم.

تفاصيل التنفيذ والتحديث موجودة في [ملف التنفيذ](client-workspace-team-foundation-implementation.ar.md).
