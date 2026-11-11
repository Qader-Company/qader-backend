# خطة إدارة العملاء من بوابة الأدمن

> الحالة: **منفذة — بانتظار تشغيل الـMigration والـSeeder، والـFeature tests**
> تاريخ المراجعة: 11 نوفمبر 2026 (بعد تحديث Figma واعتماد القرارات)
> المرجع الأساسي: Figma، ثم قرارات صاحب المشروع، ثم الكود الحالي

## 1. الهدف

تمكين الأدمن صاحب الصلاحية من:

- عرض العملاء والبحث والتصفية.
- فتح تفاصيل العميل ومراجعة الأرقام الملخصة ومساحات العمل التي يملكها.
- إيقاف حساب العميل وإعادة تفعيله.
- تفعيل وتعطيل كل Workspace. التعطيل يوقف أي نشاط خاص به.
- فتح تفاصيل الـWorkspace بكل بياناته وأرقامه وفريق العميل.
- حذف العميل أو الـWorkspace أو عضو الفريق، كـSoft Delete وبصلاحية مستقلة.

## 2. القرارات المعتمدة (11 نوفمبر 2026)

| # | الموضوع | القرار |
|---|---|---|
| 2.1 | الحذف | **أي حذف = Soft Delete، وبصلاحية مستقلة.** العناصر المحذوفة تظهر لاحقاً في صفحة محذوفات مقسمة Tabs حسب النوع، ومشروطة بصلاحية. الصفحة مؤجلة ومضافة لخطة إعدادات النظام (`docs/platform-settings.ar.md`، القسم 8) |
| 2.2 | تعطيل الـWorkspace | **يوقف أي نشاط خاص به، وعناصر طلبات التوظيف الجارية تتحول إلى `on_hold`** (القسم 9) |
| 2.3 | Managed Talents | **المواهب التي تم اتخاذ أي إجراء معها** في طلبات توظيف Workspaces العميل (القسم 10) |
| 2.4 | Active Hiring Requests | **تشمل `pending` مع `active`** |
| 2.5 | CR Number وTax Card | **يُضافان كحقلين اختياريين** للـWorkspace |
| 2.6 | Team members | **فريق العميل** (`workspace_members`) |
| 2.7 | Edit Profile | **ملغي** |
| 2.8 | الفلاتر والترتيب | **مفترضة** في القسم 7 |

## 3. الشاشات المعتمدة في Figma

| الشاشة | Node |
|---|---:|
| Clients List + قائمة Actions للصف | `3906:39855` |
| Client Details | `3906:43765` |
| Client Details + نافذة Deactivate Workspace | `5751:56332` |
| Client Details + نافذة Activate Workspace | `5751:56801` |
| Workspace Details | `5751:54933` |
| Reactivate Client | `3906:41632` |
| Suspend Client | `3906:42261` |
| Delete Client | `3906:42815` |

زر **Edit Profile** الظاهر في `5751:56332` ملغي بقرار 2.7. وزر **Add New Workspace** حُذف من Figma.

### 3.1 قائمة العملاء

- بحث: `Search by name, email, phone`.
- فلاتر: Account Status، وRegistration Date.
- الأعمدة: Full Name، وEmail، وPhone Number، وNumber of Workspaces، وRegistration Date، وStatus، وAction.
- قائمة Action للصف: Reactivate Client، وSuspend Client، وDelete Client. تُرجع حسب الحالة والصلاحية.

### 3.2 تفاصيل العميل

- الرأس: الأحرف الأولى، والاسم، والبريد، والهاتف، وتاريخ التسجيل، والحالة.
- KPIs: Total Workspaces، وActive Hiring Requests، وHired Talents، وManaged Talents.
- جدول Workspace: Search، وSort by، وFilter. الأعمدة: الشعار والاسم، وField of Work، وCreated Date، وStates (Toggle)، وAction (حذف).

### 3.3 تفاصيل الـWorkspace

- Breadcrumb: Clients / اسم العميل / اسم الـWorkspace.
- الرأس: الشعار، والاسم، والمجال، وCreated Date، وWorkspace Owner، والحالة.
- KPIs: Team Members، وHiring Requests، وHired Talents.
- Workspace Information:
  - Identity: Logo، وName.
  - Business: Industry، وBusiness Description، وAre you a company?، وCR Number، وTax Card (View / Download).
  - Location: Country، وCity.
  - Team Size.
  - Online Presence: Website، وSocial Media Platform، وURL.
- Team members (فريق العميل): بحث بالاسم أو المسمى، وفلترا All Statuses وAll Squads، والأعمدة: الاسم، وPosition، وJoined Date، وStatus، وAction (حذف)، مع ترقيم.

### 3.4 نوافذ العميل

| النافذة | الحقول |
|---|---|
| Suspend Client | Suspension Reason (إلزامي، لحماية الـAudit) |
| Reactivate Client | تأكيد فقط |
| Delete Client | Deletion Reason إلزامي، و`confirmation` (Checkbox الإقرار) إلزامي |

## 4. الوضع الحالي والتعديلات المطلوبة

| الجزء | الحالي | المطلوب |
|---|---|---|
| Admin Client Management | غير موجود | يُبنى من الصفر |
| هاتف العميل | غير موجود | عمودان في `clients` |
| حالة العميل | `users.is_active` | تبقى، مع سجل أحداث |
| حذف العميل | لا يوجد | `SoftDeletes` على `clients` |
| حالة الـWorkspace | غير موجودة | عمود `status` |
| حذف الـWorkspace | لا يوجد، و`restrictOnDelete` | `SoftDeletes` على `workspaces`. الـFK يبقى كما هو لأنه لا حذف فعلي |
| CR Number | غير موجود | عمود اختياري |
| Tax Card | غير موجود. الـWorkspace فيه Media collection `logo` فقط | Media collection `tax_card` اختيارية |
| حذف عضو الفريق | `workspace_members` فيه `SoftDeletes` بالفعل | يُعاد استخدامه |
| Industries وProfessional Fields وSocial links | متعددة | الـAPI يُرجع المصفوفة + قيمة عرض أولى |

## 5. الصلاحيات

```text
admin.clients.view     القائمة والتفاصيل وWorkspaces العميل وتفاصيل الـWorkspace وفريقه
admin.clients.manage   Suspend وReactivate وActivate/Deactivate Workspace
admin.clients.delete   حذف العميل، وحذف Workspace، وحذف عضو من فريق Workspace
```

- الحذف بصلاحية مستقلة (قرار 2.1)، ولا يدخل في `manage`.
- `PermissionSeeder` و`AdminRoleSeeder` يضيفان الصلاحيات تلقائياً لـ`super_admin` و`admin`. الأدوار المخصصة تحتاج إضافتها من شاشة الأدوار.

## 6. قاعدة البيانات

### 6.1 `clients`

| العمود | النوع |
|---|---|
| `phone_country_code` | nullable varchar(8) |
| `phone_number` | nullable varchar(24) |
| `deleted_at` | soft deletes |

### 6.2 `workspaces`

| العمود | النوع | الملاحظة |
|---|---|---|
| `status` | varchar(16) default `active` | `active` أو `inactive` |
| `cr_number` | nullable varchar(64) | اختياري |
| `deleted_at` | soft deletes | |

ومعها Media collection جديدة `tax_card`: ملف واحد اختياري، PDF أو JPG أو PNG.

### 6.3 `client_management_events`

| العمود | النوع | الملاحظة |
|---|---|---|
| `id` | bigint | |
| `client_id` | FK clients | |
| `workspace_id` | nullable FK workspaces | لأحداث الـWorkspace |
| `workspace_member_id` | nullable FK workspace_members | لحذف العضو |
| `actor_admin_id` | FK admins | |
| `action` | varchar(32) | `client_suspended`، و`client_reactivated`، و`client_deleted`، و`workspace_activated`، و`workspace_deactivated`، و`workspace_deleted`، و`workspace_member_deleted` |
| `reason` | nullable text | للإيقاف والحذف |
| `created_at` | timestamp | Immutable |

هذا السجل هو مصدر "من حذف ومتى ولماذا" لصفحة المحذوفات لاحقاً.

كل الـMigrations ترقية تحافظ على البيانات: الـWorkspaces الحالية تصبح `active`، ولا يُعاد إنشاء أي جدول.

## 7. الـAPI

كل المسارات تحت `/api/v1/admin/clients`.

| Method | Path | الصلاحية |
|---|---|---|
| GET | `/options` | view |
| GET | `/` | view |
| GET | `/{client}` | view |
| POST | `/{client}/suspend` | manage |
| POST | `/{client}/reactivate` | manage |
| DELETE | `/{client}` | delete |
| GET | `/{client}/workspaces` | view |
| GET | `/{client}/workspaces/{workspace}` | view |
| POST | `/{client}/workspaces/{workspace}/activate` | manage |
| POST | `/{client}/workspaces/{workspace}/deactivate` | manage |
| DELETE | `/{client}/workspaces/{workspace}` | delete |
| GET | `/{client}/workspaces/{workspace}/members` | view |
| DELETE | `/{client}/workspaces/{workspace}/members/{member}` | delete |

أي Workspace لا يملكه العميل، أو عضو لا يتبع الـWorkspace، يرجع `404`.

### 7.1 قائمة العملاء (افتراضات قرار 2.8)

| Query | القيم |
|---|---|
| `search` | الاسم أو البريد أو الهاتف |
| `status` | `active` أو `suspended` |
| `registration_period` | `last_7_days`، و`last_30_days`، و`last_90_days`، و`this_year`. يُستخدم كقائمة جاهزة في الفلتر |
| `registered_from` و`registered_to` | نطاق مخصص، ويُتجاهل `registration_period` عند إرسالهما |
| `sort` | `newest` (افتراضي)، و`oldest`، و`name_asc`، و`name_desc` |
| `per_page` | 1 إلى 100 |

- القائمة تعرض حسابات Client غير المحذوفة فقط.
- `workspaces_count` يحسب الـWorkspaces المملوكة غير المحذوفة.

### 7.2 تفاصيل العميل

البيانات الأساسية، و`status`، و`status_label`، و`registered_at`، و`kpis {total_workspaces, active_hiring_requests, hired_talents, managed_talents}`، و`suspension {reason, suspended_at}` عندما يكون موقوفاً، و`available_actions`.

### 7.3 Workspaces العميل (افتراضات قرار 2.8)

| Query | القيم |
|---|---|
| `search` | اسم الـWorkspace |
| `status` | `active` أو `inactive` |
| `industry_id` | فلتر بالـIndustry |
| `professional_field_id` | فلتر بمجال العمل (Field of Work) |
| `sort` | `newest` (افتراضي)، و`oldest`، و`name_asc`، و`name_desc` |

كل صف: `id`، و`name`، و`logo_url`، و`industries[]`، و`professional_fields[]`، و`field_of_work` (أول قيمة للعرض)، و`created_at`، و`status`، و`status_label`، و`available_actions`.

### 7.4 تفاصيل الـWorkspace

- الرأس: `id`، و`name`، و`logo_url`، و`industries[]`، و`created_at`، و`owner {client_id, full_name}`، و`status`، و`status_label`، و`available_actions`.
- `kpis {team_members, hiring_requests, hired_talents}`.
- `information`: `description`، و`is_company`، و`cr_number`، و`tax_card {file_name, mime_type, view_url, download_url}` أو `null`، و`country`، و`city`، و`size_band`، و`website_url`، و`social_links[]`، و`professional_fields[]`.

### 7.5 فريق الـWorkspace (افتراضات قرار 2.8)

| Query | القيم |
|---|---|
| `search` | الاسم أو البريد |
| `status` | `active` أو `suspended` (All Statuses) |
| `role_id` | فلتر **All Squads**. يُفترض أنه أدوار الـWorkspace، لأنها التقسيم الوحيد لفريق العميل |

كل صف: `id`، و`full_name`، و`email` (يُعرض بدل `@handle`)، و`avatar`، و`roles[]` (تُعرض في عمود Position)، و`joined_at`، و`status`، و`status_label`، و`is_owner`، و`available_actions`.

**لا يمكن حذف مالك الـWorkspace** من الفريق.

### 7.6 الإجراءات

| الإجراء | Body | السلوك |
|---|---|---|
| Suspend | `{reason}` | `is_active=false`، وإلغاء كل Sanctum tokens والأجهزة، وتسجيل الحدث |
| Reactivate | — | `is_active=true` وتسجيل الحدث. لا يُصدر توكن |
| Delete Client | `{reason, confirmation: true}` | راجع 7.7 |
| Activate Workspace | — | `status=active`، واستئناف العناصر التي أوقفها التعطيل بمواعيد جديدة، وتسجيل الحدث |
| Deactivate Workspace | — | `status=inactive`، وتحويل العناصر الجارية إلى `on_hold`، وتسجيل الحدث (القسم 9) |
| Delete Workspace | `{reason?}` | Soft Delete وتسجيل الحدث. أثره كأثر التعطيل، والـWorkspace لا يظهر في أي قائمة |
| Delete Member | — | Soft Delete للعضوية وتسجيل الحدث. لا يمس حساب العضو في الـWorkspaces الأخرى |

### 7.7 حذف العميل

داخل Transaction واحدة:

1. Soft Delete لسجل `clients`.
2. `users.is_active=false`، وإلغاء كل التوكنات والأجهزة.
3. Soft Delete لكل الـWorkspaces التي يملكها، فيتوقف نشاطها.
4. تسجيل حدث `client_deleted` بالسبب، وحدث `workspace_deleted` لكل Workspace حُذف معه.

لا يُحذف أي سجل تاريخي (طلبات توظيف، عروض، أحداث، طلبات مساعدة). الاستعادة لاحقاً من صفحة المحذوفات تعيد العميل وكل ما حُذف معه.

## 8. منطق الـActions

### العميل

| الحالة | Actions |
|---|---|
| Active | `suspend` (manage)، و`delete` (delete) |
| Suspended | `reactivate` (manage)، و`delete` (delete) |

### الـWorkspace

| الحالة | Actions |
|---|---|
| Active | `deactivate` (manage)، و`delete` (delete) |
| Inactive | `activate` (manage)، و`delete` (delete) |

### عضو الفريق

| الحالة | Actions |
|---|---|
| غير المالك | `delete` (delete) |
| المالك | لا شيء |

إيقاف العميل يوقف حسابه فقط، ولا يعطّل الـWorkspaces أو أعضاءها الآخرين. أما **حذفه** فيحذف الـWorkspaces المملوكة معه (7.7).

## 9. أثر تعطيل الـWorkspace (قرار 2.2)

### 9.1 طلبات التوظيف: `on_hold`

عند **Deactivate** (وعند حذف الـWorkspace)، وداخل نفس الـTransaction:

- كل عنصر في طلبات توظيف هذا الـWorkspace حالته تشغيلية (`offer`، أو`interview`، أو`accepted`) يتحول إلى **`on_hold`**، بنفس منطق `HiringItemControlWorkflow::hold` الحالي:
  - **إلغاء أي مقابلة توظيف للعنصر** (مقترحة، أو مؤكدة، أو بانتظار إعادة الجدولة)، كما في الإيقاف اليدوي، مع تسجيل حدث `interview_cancelled_by_hold`.
  - تفريغ `response_due_at` للعرض المعلّق و`decision_due_at`، فلا ينتهي أي موعد أثناء التعطيل.
  - تسجيل حدث `item_held` بالحالة السابقة والمواعيد السابقة، ومعه `source: workspace_deactivated`.
- العناصر في `pending` (طلب لم يبدأه الأدمن بعد) تبقى كما هي، لكن Start يُرفض أثناء التعطيل.
- العناصر النهائية (`rejected`، و`cancelled`، و`completed`) لا تتأثر.
- العناصر التي كانت `on_hold` يدوياً قبل التعطيل تبقى كما هي.

عند **Activate**:

- تُستأنف **فقط** العناصر التي أوقفها التعطيل (`source: workspace_deactivated`)، بنفس منطق `resume` الحالي: ترجع لحالتها السابقة، ويُعطى العرض المعلّق والقرار **مواعيد جديدة كاملة** من وقت التفعيل.
- العناصر التي أوقفها الأدمن أو العميل يدوياً تبقى `on_hold` حتى تُستأنف يدوياً.
- **المقابلات الملغاة تبقى ملغاة ولا تعود تلقائياً.** العنصر الذي كان في مرحلة `interview` يرجع لها بدون مقابلة، ويحدد الأدمن موعداً جديداً عبر Schedule Interview.

بهذا لا يضيع أي موعد أثناء التعطيل، ولا حاجة لمنطق خاص في المهام المجدولة، لأنها لا تعالج عناصر `on_hold` أصلاً.

### 9.2 باقي النشاط

| المكان | السلوك |
|---|---|
| بوابة العميل | `WorkspaceAccessService` يرفض الوصول. كل endpoints الـWorkspace (التفاصيل، والفريق، والأدوار، والـMarketplace، والاختيارات، وطلبات التوظيف، وطلبات المساعدة) ترجع `403` برسالة "This workspace is inactive". ويظهر في قائمة Workspaces العميل بحالته ليعرف السبب |
| بوابة الموهبة | عناصر هذا الـWorkspace تظهر `on_hold`، ولا يوجد ما ترد عليه |
| الأدمن | Hold وResume وCancel وباقي عمليات Hiring Workflow على عناصر هذا الـWorkspace تُرفض أثناء التعطيل، عدا العرض. وفي Help Requests يُسمح بالعرض والملاحظات فقط |
| إعادة التفعيل | تعيد الوصول وتستأنف العناصر كما في 9.1 |

## 10. الأرقام الملخصة

| KPI | الشاشة | التعريف |
|---|---|---|
| Total Workspaces | العميل | Workspaces مملوكة غير محذوفة |
| Active Hiring Requests | العميل | طلبات Workspaces العميل بحالة `pending` أو `active` (قرار 2.4) |
| Hired Talents | العميل والـWorkspace | مواهب مميزة لها عنصر `accepted` داخل طلب `completed` (قرار S07) |
| Managed Talents | العميل | **مواهب مميزة لها عنصر في طلب توظيف لـWorkspaces العميل تجاوز `pending`**، أي بدأ الأدمن التعامل معه (عرض، أو مقابلة، أو قرار، أو قبول، أو رفض، أو إيقاف، أو إلغاء) (قرار 2.3) |
| Team Members | الـWorkspace | أعضاء فريق العميل النشطون غير المحذوفين (قرار 2.6) |
| Hiring Requests | الـWorkspace | كل طلبات التوظيف للـWorkspace |

- الأعداد بالمواهب المميزة (`count distinct talent_id`)، حتى لا تتكرر الموهبة مع أكثر من طلب.
- تُحسب بـQueries مجمّعة بدون N+1.

## 11. CR Number وTax Card عند العميل (قرار 2.5)

الحقلان اختياريان في كل مكان:

- **نموذج إنشاء الـWorkspace وتعديله في بوابة العميل:** `cr_number` (nullable، حتى 64 حرف)، و`tax_card` (ملف اختياري، PDF أو JPG أو PNG، حتى 10MB)، مع إمكانية حذف الملف عند التعديل.
- **Resource الـWorkspace عند العميل:** يُرجع الحقلين.
- **تفاصيل الـWorkspace عند الأدمن:** تعرضهما مع رابطي View وDownload محميين بصلاحية `admin.clients.view`.
- لا توجد في Figma شاشة العميل لهذين الحقلين، فيتم التعديل في الـAPI وتُضاف الحقول في الواجهة لاحقاً.

## 12. ترتيب التنفيذ

1. الصلاحيات الثلاث وترجماتها.
2. Migrations: هاتف العميل والـSoft Delete، وحالة الـWorkspace وCR Number والـSoft Delete، وسجل الأحداث. ثم Media collection `tax_card`.
3. Admin: قائمة العملاء وتفاصيلهم والـKPIs.
4. Suspend وReactivate وDelete Client.
5. Workspaces العميل، وActivate وDeactivate وDelete، وتفاصيل الـWorkspace.
6. فريق الـWorkspace وحذف العضو.
7. أثر التعطيل (القسم 9): تحويل العناصر إلى `on_hold` واستئنافها، و`WorkspaceAccessService`، ومنع عمليات الأدمن.
8. CR Number وTax Card في نموذج الـWorkspace عند العميل.
9. `available_actions` في كل الـResources.
10. Feature tests، ثم أمثلة `@response` وأسماء Apidog وتوليد الـOpenAPI.

## 13. اختبارات القبول

- الأدمن بدون `admin.clients.view` يأخذ `403`، وبدون `admin.clients.delete` لا يرى `delete` ولا يستطيع الحذف.
- القائمة لا تعرض Talent ولا Admin ولا عملاء محذوفين.
- البحث يعمل على الاسم والبريد والهاتف.
- Suspend يعطّل الحساب ويلغي التوكنات، ولا يُقبل بدون سبب.
- العميل الموقوف لا يستطيع Login ولا استخدام توكن قديم.
- Delete Client بدون `confirmation=true` أو بدون سبب يرجع `422`.
- Delete Client يحذف الـWorkspaces المملوكة كـSoft Delete، ولا يحذف أي تاريخ.
- Workspace غير نشط: كل endpoints العميل عليه ترجع `403`، وعناصره الجارية `on_hold` بدون مواعيد، والمقابلات الجارية ملغاة.
- إعادة التفعيل تستأنف فقط العناصر التي أوقفها التعطيل، بحالتها السابقة ومواعيد جديدة، وتترك المُوقفة يدوياً كما هي.
- لا يمكن حذف مالك الـWorkspace من الفريق.
- `GET /{client}/workspaces/{workspace}` يرجع `404` لـWorkspace لا يملكه العميل.
- KPIs تحسب المواهب المميزة ولا تتأثر بالترقيم.

## 14. خارج النطاق

- صفحة المحذوفات والاستعادة: مؤجلة، ومخططة في `docs/platform-settings.ar.md` القسم 8.
- Edit Profile: ملغي.
- إنشاء Workspace من الأدمن: حُذف من Figma.
- إدارة أدوار فريق العميل من الأدمن.
- العقود والدفع.
- نقل ملكية الـWorkspace.

## 15. حالة التنفيذ (11 نوفمبر 2026)

| الجزء | الملفات | الحالة |
|---|---|---|
| Migration ترقية | `database/migrations/2026_11_11_000000_add_admin_client_management_schema.php` | مكتوبة، وتم التحقق منها بـ`--pretend`، ولم تُنفَّذ على أي قاعدة |
| الصلاحيات | `AdminPermissionEnum` (`CLIENTS_VIEW/MANAGE/DELETE`) + `lang/*/permissions.php` | منفذة |
| Enums وModels | `Clients/Domain`، و`WorkspaceStatusEnum`، وSoftDeletes على `Client` و`Workspace`، وMedia collection `tax_card` | منفذة |
| خدمات الأدمن | `AdminClientService`، و`AdminClientWorkspaceService`، و`ClientKpiService`، و`ClientManagementRecorder` | منفذة |
| الـAPI | `routes/v1/admin/clients.php` (14 route) + `AdminClientController` + Resources + Requests | منفذة |
| أثر التعطيل | `WorkspaceAccessService::ensureActive`، و`HiringItemControlWorkflow::holdForWorkspaceDeactivation` و`resumeAfterWorkspaceActivation`، وحمايات Start/Assign/Hold/Resume/Cancel في التوظيف وطلبات المساعدة، وإخفاؤها من `available_actions` | منفذة |
| السجل التاريخي | علاقات `workspace` و`createdByMember` و`client` تقرأ المحذوف (`withTrashed`) حتى لا تنكسر الطلبات القديمة | منفذة |
| CR Number وTax Card عند العميل | Store/Update Workspace + `WorkspaceResource` + `GET client/workspaces/{workspace}/tax-card` | منفذة |
| التوثيق | `EndpointNames` + `scribe:generate` | منفذة |
| Feature tests | — | متبقية |

### للتشغيل على أي بيئة

```bash
php artisan migrate
php artisan db:seed --class=AdminRoleSeeder
```

