# خطة مبدئية — إدارة المحذوفات والاستعادة

الحالة: **خطة مبدئية محدثة بعد مراجعة صفحات Trash في Admin وClient Portal**  
تاريخ المراجعة: **15 نوفمبر 2026**  
المرجع الأساسي: شاشات `Trash` داخل Admin وClient Portal، والقرارات المسجلة في `docs/platform-settings.ar.md`.

## 1. الهدف

توحيد حذف عناصر الأعمال في النظام كـSoft Delete، ثم إتاحة عرض العناصر المحذوفة واستعادتها من صفحات مستقلة حسب البورتال والصلاحيات، مع الحفاظ على العلاقات والسجل التاريخي ومنع أي Portal من رؤية بيانات Portal أوWorkspace أخرى.

هذه الخطة لا تنفذ أي Migration أوEndpoint الآن. تفاصيل Client Trash الأساسية ظاهرة في Figma، وأي تفاصيل UI تضاف لاحقًا تحدث في الخطة دون تغيير قواعد العزل والحذف المعتمدة هنا.

## 2. القرارات المعتمدة

1. الحذف الذي ينفذه المستخدم على **عناصر الأعمال القابلة للإدارة** يكون Soft Delete.
2. الحذف الأول من القوائم العادية يكون Soft Delete، ثم يمكن إزالة العنصر نهائيًا من واجهة Trash يدويًا بصلاحية مستقلة، أوتلقائيًا بعد انتهاء مدة الاحتفاظ. الإزالة من Trash لا تحذف Row من قاعدة البيانات.
3. عمليات الحذف التقنية الداخلية مثل Tokens وPivot rows والبيانات المؤقتة ليست جزءًا من Trash.
4. كل موديول يحتفظ بصلاحية الحذف الخاصة به؛ عرض Trash والاستعادة لهما صلاحيات مستقلة.
5. العنصر المحذوف لا يظهر في القوائم أوالتفاصيل العادية.
6. الحذف والاستعادة يسجلان: المنفذ، والتوقيت، والسبب عندما يطلبه الفلو، والسياق الذي نفذت منه العملية.
7. لا تحذف العلاقات التاريخية مثل Hiring Requests وOffers وContracts وEvents.
8. `available_actions` يحدد ما يستطيع المستخدم تنفيذه على كل عنصر محذوف، بنفس الشكل الموحد المستخدم في باقي الموديولات.
9. Soft Delete لا يغير حالة العنصر الأصلية؛ المنع ينتج من وجود `deleted_at`، وعند الاستعادة يعود العنصر بالحالة نفسها التي كان عليها قبل الحذف.
10. لا يستطيع المستخدم التعامل مع Workspace محذوفة أوأي بيانات تابعة لها، حتى لو ظلت حالات هذه البيانات كما هي في قاعدة البيانات.
11. نوع العنصر هو الذي يحدد البورتال الذي يظهر فيه: Admin Trash وClient Trash لهما أنواع مستقلة، ولا ننقل العنصر بينهما اعتمادًا على جهة الحذف فقط.
12. نسجل `deletion_origin` والسياق المرتبط بالحذف لأغراض الـAudit، حتى لو كان العنصر يظهر في Trash واحدة فقط.
13. Hiring Requests لا تدخل أي Trash ولا توفر Delete؛ إنهاؤها يتم عن طريق حالات وإجراءات دورة التوظيف الحالية.
14. مدة الاحتفاظ الافتراضية `30` يومًا، وتقرأ من Platform Setting قابلة للتعديل.
15. Soft Delete والإزالة النهائية من Trash يرفضان إذا كان العنصر مرتبطًا ببيانات تشغيلية مانعة؛ لا يدخل العنصر Trash أصلًا قبل اجتياز التحقق.
16. العنصر الذي يزال نهائيًا من Trash يظل محفوظًا في قاعدة البيانات بـ`deleted_at` و`removed_from_trash_at` لأغراض العلاقات والتاريخ، لكنه لا يظهر في القوائم العادية أوTrash ولا يمكن استعادته من الواجهة.

## 3. ما ظهر في Figma حتى الآن

### Admin Portal

يوجد مدخل مستقل باسم `Trash`، وصفحة بعنوان:

```text
Deleted items recover or permanently remove them
```

التصميم يحتوي حالات أوTabs مرتبطة بـ:

- Jobs.
- Forms.
- Talents.
- Admins.
- Roles.
- Help Requests.
- Clients.
- Master Data.

رغم ظهور Hiring Requests وWorkspaces في بعض Frames، فقد أُلغيا من نطاق Admin Trash بقرار المشروع. عبارة `permanently remove` معتمدة بمعنى إزالة العنصر نهائيًا من Trash ومنع استعادته من الواجهة، وليس حذف Row من قاعدة البيانات. الإزالة متاحة يدويًا وتتم تلقائيًا بعد مدة الاحتفاظ. رسالة `cannot be undone` تستخدم مع Remove Permanently فقط، أما Soft Delete فيوضح للمستخدم أن العنصر قابل للاستعادة.

`Jobs` تعرض Job Forms، و`Forms` تعرض المتقدمين/Form Submissions. حقول الحالة والأعداد مثل Number of Users وNumber of Workspaces وRejected/Blacklist هي بيانات عرض توضح الحالة الأصلية وليست دليلًا على السماح بحذف عنصر مرتبط. الـActivation Toggle يعرض الحالة فقط، وRestore مفترض كـAction موجود لكل نوع حسب الصلاحية.

### Client Portal

الأنواع المعتمدة مبدئيًا في Client Trash هي:

- Team members.
- Workspace Roles.
- Workspaces.

صفحات الـTabs الثلاث موجودة حاليًا في Figma، وتفاصيلها المعتمدة مبدئيًا:

| Tab | الأعمدة الظاهرة |
| --- | --- |
| Team | Name، Email، Phone Number، Role، Created Date، Activation، Action |
| Roles | Role، Number of Users، Activation، Action |
| Workspaces | Workspace Name، Field of Work، Created Date، State، Action |

التصميم يعرض Search وFilter، ويعرض Toggle وAction icon. نفترض أن Restore وRemove Permanently متاحان لكل نوع حسب الصلاحية. الـActivation Toggle داخل Trash يعرض الحالة المحفوظة فقط ولا يغيرها. بيانات الأعداد والحالات داخل الصفوف Dummy Data ولا تستخدم لاشتقاق قواعد الحذف.

### ملاحظات Figma الفعلية المطلوب تصحيحها في الواجهة

- Confirmation الخاص بالـSoft Delete يجب أن يوضح أن العنصر سينتقل إلى Trash ويمكن استعادته، ولا يستخدم `This action cannot be undone`.
- `This action cannot be undone` صحيح فقط في Confirmation الخاص بـRemove Permanently، لأن العنصر لن يعود قابلًا للاستعادة من الواجهة رغم بقاء سجله في قاعدة البيانات.
- نافذة حذف Team Member التي تعرض `Delete Admin?` بها خطأ نصي، والصحيح أن تشير إلى Team Member.
- ظهور Counts أوStatuses غير متوافقة مع بيانات الاختبار لا يغير قواعد الـBackend؛ هذه بيانات تصميم تجريبية وليست Flow معتمدًا.

تثبت كذلك قواعد الظهور والعزل التالية:

- Team Trash وRoles Trash يتغيران حسب الـWorkspace النشطة المختارة، وكل Query أوRestore فيهما مقيد بـ`workspace_id` الحالية.
- Workspaces Trash مشتركة على مستوى حساب Client، وتعرض فقط الـWorkspaces المحذوفة التي يملكها الحساب، ولا تعتمد على Workspace نشطة.
- لا يستطيع عضو رؤية محذوفات Workspace غير منضم إليها أوغير مخول داخلها.
- رؤية Workspace في حسابه الشخصي أوامتلاكه Workspace أخرى لا تمنحه حق رؤية محذوفات غير مصرح بها.
- الصلاحيات تحسم الوصول، وليس مجرد معرفة `workspace_id`.
- لا يعاد استخدام Admin Trash endpoint للعميل.
- Workspaces tab متاح من مستوى حساب Client دون الدخول إلى Workspace محذوفة، بينما Team وRoles يتبعان الـWorkspace النشطة.
- Team وRoles وWorkspaces لا تظهر في Admin Trash؛ مكانها Client Trash فقط.
- لا يسمح بحذف أي منها إذا كانت مرتبطة ببيانات تشغيلية تمنع الحذف.
- Workspace المحذوفة تختفي فورًا من قائمة اختيار الـWorkspaces العادية، ولا تظهر إلا في `Client Trash > Workspaces` إلى أن تتم استعادتها.
- يظل Client Trash قابلًا للوصول للـOwner حتى إذا لم يتبق له أي Workspace نشطة.

## 4. الفصل المعماري

Trash موديول إداري مستقل وليس جزءًا من Platform Settings. مدة الاحتفاظ فقط تحفظ في Platform Settings، بينما العرض والاستعادة والإزالة النهائية من Trash Operations داخل موديول Trash.

الموديول المقترح:

```text
app/Modules/V1/Trash/
├── Application
│   ├── Contracts
│   ├── DTOs
│   ├── Services
│   └── UseCases
├── Domain
│   ├── ValueObjects
│   └── Exceptions
├── Infrastructure
│   └── Providers
└── Presentation
    └── Http
        ├── Controllers
        ├── Requests
        └── Resources
```

لا ينشئ الموديول جدولًا يجمع نسخ العناصر المحذوفة. كل سجل يظل في جدوله الأصلي باستخدام `deleted_at`، ويضاف `removed_from_trash_at` للأنواع التي تدخل Trash لتمييز السجل الذي انتهت إمكانية استعادته. يقرأ Trash من الموديولات المسجلة في Registry واضح.

## 5. عقدة التكامل مع الموديولات

بدل كتابة Controller ضخم يعرف تفاصيل كل جدول، كل نوع محذوف ينفذ Adapter بعقد موحد، مثل:

```php
interface TrashResourceHandler
{
    public function type(): string;

    public function paginate(TrashQuery $query): LengthAwarePaginator;

    public function restore(int $id, TrashActor $actor): void;

    public function removeFromTrash(int $id, TrashActor $actor): void;
}
```

كل Handler مسؤول عن:

- تطبيق Portal وWorkspace scope.
- تطبيق نطاق الظهور حسب نوع العنصر والعميل أوWorkspace المتأثرة.
- استخدام `onlyTrashed()` مع شرط `removed_from_trash_at IS NULL`.
- بناء الفلاتر والترتيب الخاصين بالنوع.
- التحقق من صلاحية الاستعادة.
- معالجة تعارض Unique values بعد الحذف.
- استعادة العلاقات التابعة المعتمدة فقط.
- تسجيل Event الاستعادة.

الـ`type` لا يتحول مباشرة إلى اسم Model أوTable من مدخل المستخدم؛ يقبل فقط قيم Registry معتمدة لمنع الوصول إلى Models غير مصرح بها.

## 6. صلاحيات مبدئية

### Admin

```text
admin.trash.view
admin.trash.restore
admin.trash.remove
```

صلاحية Soft Delete تظل مستقلة داخل الموديول، مثل `admin.clients.delete`. الوصول إلى صفحة Trash يحتاج `admin.trash.view`، والاستعادة تحتاج `admin.trash.restore`، والإزالة النهائية من Trash تحتاج `admin.trash.remove`. لا نربط الاستعادة أوالإزالة بصلاحية Manage عامة أخرى.

### Client

```text
client.trash.view
client.trash.restore
client.trash.remove
```

صلاحية Soft Delete تظل مستقلة لكل موديول. صلاحيات عرض واستعادة وإزالة Team وRoles من Trash تطبق داخل Workspace Role وعلى الـWorkspace النشطة فقط. Workspaces Trash تعرض للحساب الـWorkspaces التي يملكها فقط، ولا تعرض Workspaces يكون عضوًا فيها دون ملكيتها.

## 7. API مبدئي

### Admin Portal

```text
# Job Forms — تبويب Jobs
GET  /api/v1/admin/trash/job-forms
POST /api/v1/admin/trash/job-forms/{job_form}/restore
POST /api/v1/admin/trash/job-forms/{job_form}/remove

# Form Submissions — تبويب Forms
GET  /api/v1/admin/trash/form-submissions
POST /api/v1/admin/trash/form-submissions/{submission}/restore
POST /api/v1/admin/trash/form-submissions/{submission}/remove

# Talents
GET  /api/v1/admin/trash/talents
POST /api/v1/admin/trash/talents/{talent}/restore
POST /api/v1/admin/trash/talents/{talent}/remove

# Admins
GET  /api/v1/admin/trash/admins
POST /api/v1/admin/trash/admins/{admin}/restore
POST /api/v1/admin/trash/admins/{admin}/remove

# Roles
GET  /api/v1/admin/trash/roles
POST /api/v1/admin/trash/roles/{role}/restore
POST /api/v1/admin/trash/roles/{role}/remove

# Help Requests
GET  /api/v1/admin/trash/help-requests
POST /api/v1/admin/trash/help-requests/{help_request}/restore
POST /api/v1/admin/trash/help-requests/{help_request}/remove

# Clients
GET  /api/v1/admin/trash/clients
POST /api/v1/admin/trash/clients/{client}/restore
POST /api/v1/admin/trash/clients/{client}/remove

# Master Data — كل نوع منفصل
GET  /api/v1/admin/trash/countries
POST /api/v1/admin/trash/countries/{country}/restore
POST /api/v1/admin/trash/countries/{country}/remove

GET  /api/v1/admin/trash/cities
POST /api/v1/admin/trash/cities/{city}/restore
POST /api/v1/admin/trash/cities/{city}/remove

GET  /api/v1/admin/trash/currencies
POST /api/v1/admin/trash/currencies/{currency}/restore
POST /api/v1/admin/trash/currencies/{currency}/remove

GET  /api/v1/admin/trash/languages
POST /api/v1/admin/trash/languages/{language}/restore
POST /api/v1/admin/trash/languages/{language}/remove

GET  /api/v1/admin/trash/industries
POST /api/v1/admin/trash/industries/{industry}/restore
POST /api/v1/admin/trash/industries/{industry}/remove

GET  /api/v1/admin/trash/professional-fields
POST /api/v1/admin/trash/professional-fields/{professional_field}/restore
POST /api/v1/admin/trash/professional-fields/{professional_field}/remove

GET  /api/v1/admin/trash/job-titles
POST /api/v1/admin/trash/job-titles/{job_title}/restore
POST /api/v1/admin/trash/job-titles/{job_title}/remove

GET  /api/v1/admin/trash/skills
POST /api/v1/admin/trash/skills/{skill}/restore
POST /api/v1/admin/trash/skills/{skill}/remove

GET  /api/v1/admin/trash/tools
POST /api/v1/admin/trash/tools/{tool}/restore
POST /api/v1/admin/trash/tools/{tool}/remove
```

لا يوجد Route عام يحتوي `{type}` ولا Endpoint باسم `trash/types`. كل Tab له Routes وController وRequests وResource موثقة بصورة مستقلة، حتى تكون الـValidation والـResponse والـPermissions الخاصة بكل موديول واضحة ولا يعتمد الربط على String ديناميكي قادم من المستخدم. يمكن أن يظل Registry/Handler الداخلي لإعادة استخدام لوجيك Trash فقط، دون كشفه كـAPI عامة.

### Client Portal — مبدئي فقط

```text
GET  /api/v1/client/trash/workspaces
POST /api/v1/client/trash/workspaces/{workspace}/restore
POST /api/v1/client/trash/workspaces/{workspace}/remove

GET  /api/v1/client/workspaces/{workspace}/trash/team
POST /api/v1/client/workspaces/{workspace}/trash/team/{member}/restore
POST /api/v1/client/workspaces/{workspace}/trash/team/{member}/remove

GET  /api/v1/client/workspaces/{workspace}/trash/roles
POST /api/v1/client/workspaces/{workspace}/trash/roles/{role}/restore
POST /api/v1/client/workspaces/{workspace}/trash/roles/{role}/remove
```

مسارات Workspaces Trash على مستوى الحساب لأنها مشتركة ولا تحتاج Workspace نشطة. مسارات Team وRoles داخل Workspace حتى يطبق العزل والصلاحيات الخاصة بالـWorkspace المختارة. أسماء المسارات مبدئية حتى التنفيذ، لكن فصل الـScope قرار معتمد.

يعرف Frontend الـTabs المعتمدة من التصميم، وكل List endpoint يرجع Pagination count الخاص بتبويبه. إخفاء Tab غير المسموح به يتم من صلاحيات المستخدم التي يعيدها Auth/User response، ولا يحتاج Endpoint ديناميكية لتعداد الأنواع.

Response القائمة يحافظ على شكل Pagination الموحد، وكل عنصر يعيد على الأقل:

```json
{
  "id": 15,
  "type": "clients",
  "reference": "Client name or code",
  "deleted_at": "2026-11-12T10:00:00Z",
  "deleted_by": {
    "id": 4,
    "name": "Admin Name",
    "portal": "admin"
  },
  "deletion_origin": "admin",
  "deletion_reason": null,
  "scheduled_removal_at": "2026-12-12T10:00:00Z",
  "available_actions": ["restore", "remove_from_trash"]
}
```

التفاصيل الخاصة بكل نوع توضع داخل `data` أوResource متخصصة، ولا نفرض أعمدة موحدة لا تناسب كل الموديولات.

## 8. قواعد الاستعادة

1. الاستعادة داخل Transaction.
2. `404` إذا كان العنصر غير موجود أوغير محذوف أوخارج Scope المستخدم؛ لا نكشف وجود بيانات Tenant أخرى.
3. `403` عند غياب الصلاحية العامة قبل محاولة الوصول للعنصر.
4. `409 Conflict` إذا تعذر الاسترجاع بسبب تعارض Business، مثل إعادة استخدام Email أوCode فريد.
5. استعادة Parent لا تعيد كل Child محذوف تاريخيًا بصورة عمياء.
6. نحتاج ربط الحذف الجماعي بـOperation/Event identifier حتى نعيد فقط العناصر التي حذفت نتيجة نفس العملية.
7. حذف User يمنعه من تسجيل الدخول ويلغي جلساته، مع الاحتفاظ بحالته الأصلية وحجز بياناته الفريدة حتى لا ينشئ حسابًا جديدًا بنفس البيانات.
8. استعادة User تعيده كما كان قبل الحذف، مع حالته وعلاقاته غير المحذوفة، لكنه يسجل الدخول من جديد ولا تعاد Tokens القديمة.
9. لا يسمح بإعادة التسجيل بنفس بيانات User أثناء وجوده في Trash. عند إزالته نهائيًا من Trash يجب تحرير بيانات التسجيل الفريدة مع إبقاء السجل في قاعدة البيانات؛ آلية تحرير القيم تحتاج تصميمًا يحافظ على الـAudit والعلاقات ولا يكسر Unique constraints.
10. لا يسمح بحذف Role عليها Users؛ يجب إزالة الإسنادات أولًا. لذلك لا يحتاج Restore إلى إعادة إسناد مستخدمين سابقين.
11. تسجل الاستعادة في Audit/Event log مع المنفذ والوقت والعنصر الأصلي.

### 8.1 حذف Help Request

- يسمح للأدمن بحذف Help Request كـSoft Delete.
- تختفي من القوائم والتفاصيل العادية، وتظهر في Admin Trash.
- إذا كانت ظاهرة لصاحب الطلب في Client Portal فإن حذف الأدمن يخفيها منه أيضًا، لكنها لا تدخل Client Trash ضمن الأنواع المعتمدة حاليًا.
- الاستعادة تعيدها بالحالة والمحتوى نفسيهما دون إعادة فتحها أوتغيير حالتها تلقائيًا.

### 8.2 منع حذف الحسابات والعناصر غير الفارغة

الحذف يرفض بـ`409 Conflict` عندما توجد بيانات تشغيلية مرتبطة. التحقق ينفذ داخل Transaction قبل Soft Delete، ولا يعتمد على Frontend فقط.

#### Client account

يمكن للأدمن حذف Client إذا كان حسابه فارغًا فقط. يمنع الحذف عند وجود Workspace يملكها أوعضوية في Workspace أوطلبات توظيف أوطلبات مساعدة أوSelections أوContracts أوPayments أوأي عملية تشغيلية مرتبطة به.

#### Talent account

يمكن للأدمن حذف Talent إذا كان حسابه فارغًا فقط. يمنع الحذف عند وجود Talent Application أوMarketplace Selection أوHiring Request Item أوInterview أوOffer أوContract أوPayment أوأي عملية تشغيلية مرتبطة به. بيانات البروفايل المملوكة للحساب وحده لا تعد ارتباطًا خارجيًا مانعًا ويمكن أن تظل تابعة للحساب المحذوف.

#### Client Team member

الحذف هنا للـWorkspace Membership فقط ولا يحذف User account. يمنع حذف العضو إذا أنشأ أويمتلك Records تشغيلية داخل الـWorkspace تحتاج بقاء العضوية كمرجع. وجود Role assignment وحده لا يمنع Soft Delete للعضوية، وتظل الإسنادات محفوظة حتى تعود العضوية كما كانت عند Restore. عند Remove Permanently تظل السجلات والعلاقات محفوظة تاريخيًا، لكن العضوية لا تعود ظاهرة أوقابلة للاستعادة.

#### Admin Role وWorkspace Role

لا تحذف Role إذا كانت مسندة إلى مستخدم أوعضو واحد أوأكثر. بعد إزالة الإسنادات يمكن حذفها Soft Delete واستعادتها لاحقًا بدون إسنادات. أعداد المستخدمين الظاهرة على Rows في Figma بيانات تصميم تجريبية ولا تلغي هذا الشرط.

#### Workspace

لا تحذف Workspace إلا إذا كانت فارغة. يمنع الحذف عند وجود Team members غير المالك، أوTalent selections، أوHiring Requests، أوHelp Requests، أوContracts، أوPayments، أوProjects، أوTasks، أوأي بيانات تشغيلية أخرى تابعة لها. وجود Owner والبيانات الأساسية للـWorkspace لا يمنع الحذف.

### 8.3 Hiring Requests ليست قابلة للحذف

- لا يوجد Delete أوRestore لـHiring Request.
- لا تظهر Hiring Requests في Admin Trash أوClient Trash.
- تستخدم حالات وإجراءات دورة التوظيف مثل Cancel/Reject/Complete بدل الحذف.
- تظل Items وInterviews وOffers والتاريخ المرتبط محفوظة ومتاحة حسب صلاحيات وحالات الفلو المعتادة.

### 8.4 الإزالة النهائية من Trash ومدة الاحتفاظ

- القيمة الافتراضية لمدة الاحتفاظ `30` يومًا من Platform Setting باسم `trash.retention_days`.
- يعرض كل عنصر `scheduled_removal_at` للـFrontend، وتحسب ديناميكيًا من `deleted_at` ومدة الاحتفاظ الحالية.
- المستخدم الذي يملك صلاحية `trash.remove` يستطيع إزالة العنصر نهائيًا من Trash قبل انتهاء المدة.
- الإزالة اليدوية أوالتلقائية تعيد فحص العلاقات المانعة داخل Transaction؛ عند وجود ارتباط يرجع اليدوي `409 Conflict` ويتجاوز الـScheduler العنصر دون إخفائه.
- التنفيذ يضبط `removed_from_trash_at` ولا ينفذ `DELETE` أو`forceDelete()` على Row قاعدة البيانات، ويسجل Audit event مستقلًا يتضمن المنفذ أو`system` ووقت الإزالة.
- القوائم العادية تستبعد `deleted_at`، وقوائم Trash تعرض فقط ما لديه `deleted_at` ولا يملك `removed_from_trash_at`.
- Job يومي يعالج العناصر المستحقة على دفعات، ويكون Idempotent وآمنًا عند التشغيل المتكرر.
- العنصر الذي انتهت مدته لكنه غير مؤهل للإزالة يظل في Trash حتى تزول العلاقات المانعة أوتتم استعادته.
- رسالة `This action cannot be undone` تظهر في Confirmation الإزالة النهائية من Trash فقط، ومعناها أن الاستعادة من الواجهة لن تعود متاحة.
- تغيير مدة الاحتفاظ من Platform Settings يؤثر على كل العناصر التي ما زالت ظاهرة في Trash؛ يعاد حساب `scheduled_removal_at` لها من `deleted_at` باستخدام القيمة الجديدة. تقليل المدة قد يجعل عنصرًا مستحقًا للإزالة في التشغيل التالي للـScheduler، وزيادتها تمدد مدة ظهوره.

## 9. حالة الكود الحالية

### يدعم Soft Delete بالفعل

- `clients`.
- `workspaces`.
- `workspace_members`.
- Job Forms (`job_postings`).
- Job Form Submissions.

### يحتاج مراجعة قبل إدخاله في Trash

- Admin Roles وClient Workspace Roles.
- Admins.
- Talents.
- Help Requests.
- Master Data بأنواعها.
- Talent Review FAQs.

وجود Delete endpoint لا يعني أن النوع جاهز للـTrash؛ يجب أن يدعم Model وSchema وQueries وRelations الاستعادة بصورة آمنة.

### غير منفذ حاليًا

- Routes وControllers الخاصة بصفحة Trash.
- Registry/Handlers.
- Restore endpoints.
- Remove-from-Trash endpoints والـScheduler.
- صلاحيات Trash.
- توحيد Audit metadata لكل الأنواع.
- Client Trash APIs والـscope الموثق في هذه الخطة.

## 10. مراحل التنفيذ المقترحة

### المرحلة الأولى — تثبيت العقد والسياسة

- اعتماد Admin Trash بدون Hiring Requests وWorkspaces.
- اعتماد أعمدة وأكشنز صفحات Client للأنواع: Team وRoles وWorkspaces.
- تحديد قواعد Cascade restore لكل نوع.
- تحديد Relations المانعة للحذف بصورة نهائية لكل نوع.
- تصحيح نصوص Confirm Delete المذكورة في مراجعة Figma.

### المرحلة الثانية — تجهيز الموديولات

- إضافة `SoftDeletes` فقط للجداول المعتمدة التي لا تدعمه.
- تعديل Queries العادية لاستبعاد المحذوف تلقائيًا.
- تسجيل delete/restore audit data.
- منع Hard Delete من جميع Endpoints؛ Trash endpoint المحمي يضبط `removed_from_trash_at` فقط.
- إضافة اختبارات Regression لكل Delete endpoint حالي.

### المرحلة الثالثة — Admin Trash

- تنفيذ Registry وHandlers للأنواع المعتمدة.
- تنفيذ الصلاحيات والـEndpoints والResources.
- تنفيذ Remove from Trash اليدوي والـScheduler المعتمد على مدة الاحتفاظ.
- ربط Tabs والأعداد والفلاتر.
- اختبارات الاستعادة والتعارضات والعلاقات.

### المرحلة الرابعة — Client Trash

- تنفيذ Workspaces Trash كقائمة مشتركة على مستوى حساب المالك.
- تنفيذ Team وRoles Trash داخل الـWorkspace النشطة المختارة.
- اختبار Owner والعضو متعدد الـWorkspaces والصلاحيات المخصصة.
- التأكد أن تعديل Path parameter لا يكشف بيانات Workspace أخرى.

## 11. الاختبارات الضرورية

- الحذف يخفي العنصر من List وShow العاديين.
- العنصر يظهر في Trash الصحيح فقط.
- المستخدم بدون `view` يأخذ `403` ولا يرى المدخل.
- المستخدم بدون `restore` يرى العنصر دون Action الاستعادة إذا سمح له بالعرض.
- Restore يعيد العنصر مرة واحدة فقط ويكون Idempotent على مستوى النتيجة المتوقعة.
- محاولة Restore من Portal أوWorkspace أخرى لا تكشف العنصر.
- تعارض Unique value يرجع `409` برسالة واضحة ولا ينفذ استعادة جزئية.
- Parent/Child restore يعيد فقط العناصر التابعة لنفس عملية الحذف المعتمدة.
- حذف User يمنع تسجيل الدخول وإعادة التسجيل بنفس البيانات، واستعادته تعيده بحالته السابقة.
- حذف Client أوTalent مرتبط ببيانات تشغيلية يرجع `409` ولا ينفذ حذفًا جزئيًا.
- Role المسندة وWorkspace غير الفارغة وعضوية مرتبطة بعمليات تشغيلية لا تقبل الحذف.
- Workspace المحذوفة وكل البيانات التابعة لها غير قابلة للوصول، دون تغيير حالات سجلاتها.
- Team وRoles وWorkspaces تظهر في Client Trash فقط ولا تظهر في Admin Trash.
- Hiring Requests لا تملك Delete ولا تظهر في أي Trash.
- حذف Help Request من Admin يخفيها من القوائم مع بقاء حالتها كما هي.
- المستخدم بدون `trash.remove` لا يرى Action الإزالة النهائية ويحصل على `403` عند استدعائه.
- Remove from Trash اليدوي يرجع `409` عند وجود علاقة مانعة ولا يغير أي جزء.
- الـScheduler يخفي العناصر المؤهلة من Trash بعد مدة الاحتفاظ ويتجاوز غير المؤهل منها دون حذف Rows.
- تشغيل الـScheduler أكثر من مرة لا يكرر Events أويسبب خطأ للعناصر المزالة من Trash بالفعل.
- السجل المزال نهائيًا لا يظهر في Trash ولا يقبل Restore، لكنه يظل موجودًا في قاعدة البيانات وعلاقاته التاريخية سليمة.
- لا تنكسر Hiring Requests أوContracts أوEvents المرتبطة بسجل محذوف.

## 12. القرارات المعتمدة قبل التنفيذ

1. Soft Delete، والوصول إلى Trash، والاستعادة، وRemove from Trash صلاحيات منفصلة.
2. الحذف لا يغير حالة Client أوWorkspace أوالعناصر التابعة؛ `deleted_at` وحده يمنع التعامل معها.
3. لا يمكن الوصول إلى Workspace محذوفة أوأي بيانات تابعة لها.
4. حذف User يمنع دخوله ويحجز بياناته من إعادة التسجيل، والاستعادة تعيده كما كان قبل الحذف.
5. لا تحذف Role مرتبطة بمستخدمين؛ يجب إزالة الإسنادات أولًا.
6. كل أنواع Master Data القابلة للحذف تدخل Trash. المقصود بـ"References تشغيلية" أن Country أوCurrency أوSkill مثلًا قد تكون مستخدمة بالفعل في Talent Profile أوOffer أوContract. عند حذف Master Data نخفيها من الاختيارات الجديدة، لكن لا نمسح العلاقات والسجلات القديمة التي استخدمتها، وتظل قابلة للعرض في التاريخ ثم تعود للاختيار عند الاستعادة.
7. توجد Retention policy افتراضية `30` يومًا وقابلة للتعديل من Platform Settings. بعد انتهائها يخفي النظام العنصر نهائيًا من Trash إذا ظل غير مرتبط ببيانات مانعة، كما يمكن إخفاؤه يدويًا قبلها بالصلاحية المناسبة. السجل لا يحذف من قاعدة البيانات.
8. Help Requests تقبل Soft Delete من Admin وتظهر في Admin Trash فقط.
9. Hiring Requests وWorkspaces لا تظهر في Admin Trash، وHiring Requests ليست قابلة للحذف أصلًا.
10. Admin يستطيع حذف Client أوTalent فقط عندما يكون الحساب فارغًا وغير مرتبط بأي Records تشغيلية.
11. Client Trash تحتوي Team وRoles وWorkspaces، ولا يسمح بحذف أي منها إذا كانت مرتبطة ببيانات تشغيلية مانعة.
12. Workspace المحذوفة تختفي من قائمة اختيار الـWorkspaces وتظهر فقط في Client Trash، ويظل الوصول للـTrash ممكنًا عند حذف آخر Workspace.
13. Team وRoles Trash يتغيران حسب الـWorkspace النشطة المختارة.
14. Workspaces Trash مشتركة على مستوى حساب Client، وتعرض الـWorkspaces المحذوفة المملوكة له فقط.

## 13. جاهزية القرار

قرار تأثير مدة الاحتفاظ محسوم: القيمة الحالية تطبق على جميع العناصر التي ما زالت ظاهرة في Trash. يمكن بدء التنفيذ لكل نوع بعد تثبيت علاقاته المانعة للحذف، مع الالتزام بسياسة Soft Delete والـAudit والإزالة من Trash دون حذف Row من قاعدة البيانات.
