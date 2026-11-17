# طلبات المساعدة — Request Qader Assistance / Help Requests

الحالة: **معتمدة — قيد التنفيذ (الكود الأساسي مكتوب، الاختبارات والتوثيق متبقيان)**
تاريخ الإعداد: 9 نوفمبر 2026
تاريخ اعتماد القرارات: 9 نوفمبر 2026
النطاق: العميل يرسل طلب مساعدة من داخل الـWorkspace، والأدمن يستقبله ويعيّنه ويتابعه ويغلقه بنتيجة.

## 1. قاعدة الخطة

- Figma هي المرجع الأساسي، ولا يُستخدم أي Frame داخل قسم `trash...`.
- البيانات الظاهرة في التصميم (أسماء، أرقام، تواريخ) تجريبية وليست Business Rules.
- لا تُضاف حالات أو أعمدة أو Endpoints أو صلاحيات غير ظاهرة في Figma إلا بقرار صريح من صاحب المشروع.
- يُعاد استخدام أنماط Hiring Requests الموجودة: التعيين، و`all/mine`، و`available_actions`، وسجل الأحداث.

## 2. مصادر Figma

| البورتال | الشاشة | Node |
| --- | --- | --- |
| Client | كارت Get Help from Qader في الـDashboard | `3197:16634` |
| Client | نموذج الطلب — الحالة الافتراضية | `3884:35731` |
| Client | نموذج الطلب — اختيار `Other` يُظهر "Tell us what you need" | `3884:35998` |
| Client | نافذة نجاح الإرسال | `3884:36212` |
| Admin | قائمة Help Requests (All / My) | `3921:46209` |
| Admin | نافذة Assigned Admin | `3953:43205` |
| Admin | تفاصيل طلب جديد + Start Handling | `3924:48279` |
| Admin | إضافة Internal Note | `3939:42367` |
| Admin | تفاصيل طلب مُعيَّن + Close Request | `3930:49661` |
| Admin | نافذة Close Request ونتائج الإغلاق | `3941:42237` |

## 3. ما يثبته Figma

### 3.1 Client — إرسال الطلب

- نقطتا الدخول: كارت "Get Help from Qader / Request assistance" في الـDashboard، وزر عائم "Get Help From Qader".
- الصفحة: "Request Qader Assistance — Tell us about your hiring needs and our team will reach out to help you get started."
- الحقول:
  - **What type of help are you looking for?** قائمة اختيار.
  - **Tell us what you need** نص حر، يظهر عند اختيار `Other`.
  - **How would you like to contact you?** اختيار واحد: `Email` أو `Whatsapp`، ومعه حقل قيمة جهة التواصل.
- ملاحظة أسفل النموذج: "Your workspace and contact information will be included automatically. Our team typically responds within 1 business day." أي أن الطلب مربوط بالـWorkspace الحالي وبالمستخدم المرسل.
- `Cancel` و`Submit Request`.
- نافذة النجاح تعرض: Request Title، وReference ID، وContact Preference، وزر Back to Dashboard.
- **لا توجد** في Figma قائمة لطلبات العميل السابقة، ولا شاشة تفاصيل، ولا تعديل أو إلغاء من العميل.

### 3.2 Admin — قائمة Help Requests

- تبويبا `All Requests` و`My Requests`.
- بحث بـ: Request ID، وClient Name، وWorkspace Name.
- فلاتر: Created Date، وHelp Type، وStatus، وAssigned Admin.
- الأعمدة: Request ID، وClient Name، وWorkspace Name، وHelp Type، وPreferred Contact، وAssigned To، وCreated Date، وStatus، وAction.
- Checkbox لكل صف، مع نافذة Assigned Admin (بحث + اختيار أدمن واحد + Assign)، أي تعيين جماعي لعدة طلبات.
- ترقيم الصفحات (Previous / Next).
- قيم الحالة الظاهرة: `New`، و`In Progress`، و`Closed`. وتظهر أيضاً شارة `Reassign`، وهي Action حسب الصلاحية وليست حالة (القرار 4.6).
- قيم Help Type الظاهرة كبيانات تجريبية: `Technical Issue`، و`Onboarding Help`، و`Other`.

### 3.3 Admin — تفاصيل الطلب

- **Request Details:** Request ID، وStatus، وCreated Date، وHelp Type، وPreferred Contact Method (القناة والقيمة)، وAssigned To مع أيقونة تعيين/إعادة تعيين.
- **Client Information:** Client Name، وWorkspace Name (روابط).
- **Request Description.**
- **Internal Notes:** عددها، وكل ملاحظة بنصها وكاتبها ووقتها، وزر `Add Note` (Textarea + Cancel/Save). لا يظهر تعديل أو حذف للملاحظات.
- زر الإجراء الرئيسي:
  - `Start Handling` عندما يكون الطلب `New`، ويظهر حتى لو كان الطلب غير مُعيَّن.
  - `Close Request` بعد بدء المعالجة.

### 3.4 Admin — إغلاق الطلب

نافذة "Close Request — Select the outcome for request REQ-1003 and provide details"، وفيها نتيجة واحدة إلزامية:

| النتيجة | الوصف في Figma | الحقول |
| --- | --- | --- |
| New Hiring Request Created | تم إنشاء Hiring Request نتيجة المساعدة | Hiring Request ID (قائمة) + Additional Details |
| Client Request Clarified | تم توضيح الطلب وحله بالتواصل | Resolution Details |
| Issue / Problem | تم اكتشاف مشكلة أثناء المعالجة | Description |

ثم `Cancel` و`Submit & Close Request`.

## 4. القرارات المعتمدة من صاحب المشروع

| # | الموضوع | القرار |
| --- | --- | --- |
| 4.1 | أنواع المساعدة | Enum ثابت في الكود (`HelpRequestTypeEnum`) بما قد يحتاجه العميل: `hiring_support`، و`finding_talent`، و`onboarding_help`، و`technical_issue`، و`other`. |
| 4.2 | الوصف | إلزامي مع `other` فقط، واختياري لباقي الأنواع. |
| 4.3 | Request Title | العنوان هو اسم نوع المساعدة المترجم، ويُرجع في الحقل `title`. لا يوجد عمود عنوان. |
| 4.4 | الـReference | `REQ-` + (1000 + id)، مثل `REQ-1003`، ويُستخدم عند العميل والأدمن. |
| 4.5 | صلاحية الإرسال | صلاحية جديدة `client.help-requests.create`. الـOwner يملكها تلقائياً لأن `WorkspaceAccessService` يسمح لدور `owner` بكل الصلاحيات، ويمكن منحها لأدوار أخرى. |
| 4.6 | Assign / Reassign | ليستا حالات، بل Actions حسب الصلاحية، مثل طلبات التوظيف وطلبات الانضمام كموهبة. تُرجع للأدمن داخل `available_actions` حسب صلاحياته وحالة الطلب. الحالات: `new` و`in_progress` و`closed` فقط. |
| 4.7 | الإشعارات | مؤجلة. يُسجَّل الحدث في `help_request_events` فقط. |

قرارات مكمّلة اعتُمدت بناءً على الاقتراحات:

- Start Handling متاح لطلب غير مُعيَّن، ويعيّن الأدمن الحالي ضمنياً (نفس قاعدة S02).
- قائمة Hiring Request ID في نافذة الإغلاق تقتصر على طلبات التوظيف التابعة لنفس الـWorkspace.
- لا قائمة ولا تفاصيل لطلبات المساعدة عند العميل، لأنها غير ظاهرة في Figma.
- القيمة الافتراضية لجهة التواصل هي بريد المستخدم. لا يوجد هاتف مخزَّن للمستخدم حالياً، فيُدخل رقم Whatsapp يدوياً.

## 5. السكيما (منفذة)

### 5.1 `help_requests`

| العمود | النوع | ملاحظات |
| --- | --- | --- |
| `id` | bigint | |
| `reference` | varchar(32) nullable unique | `REQ-1003`، يُملأ مباشرة بعد الإنشاء من الـid |
| `workspace_id` | FK workspaces | restrictOnDelete |
| `created_by_member_id` | FK workspace_members | نفس نمط Hiring Requests |
| `help_type` | varchar(32) | قيمة من `HelpRequestTypeEnum` |
| `description` | text nullable | إلزامي مع `other` |
| `contact_method` | varchar(16) | `email` أو `whatsapp` |
| `contact_email` | varchar nullable | عند `email` |
| `contact_phone_country_code` | varchar nullable | عند `whatsapp` |
| `contact_phone_number` | varchar nullable | عند `whatsapp` |
| `status` | varchar(16) | `new` و`in_progress` و`closed` |
| `assigned_admin_id` | FK admins nullable | nullOnDelete |
| `started_at` | timestamp nullable | |
| `closed_at` | timestamp nullable | |
| `closed_by_admin_id` | FK admins nullable | |
| `closure_outcome` | varchar(32) nullable | `new_hiring_request_created` أو `client_request_clarified` أو `issue_problem` |
| `closure_details` | text nullable | Additional Details أو Resolution Details أو Description |
| `resulting_hiring_request_id` | FK hiring_requests nullable | عند `new_hiring_request_created` |
| timestamps | | |

الفهارس: `(status, created_at)`، و`(assigned_admin_id, status, created_at)`، و`(workspace_id, created_at)`، و`help_type`.

### 5.2 `help_request_notes`

| العمود | النوع |
| --- | --- |
| `id` | bigint |
| `help_request_id` | FK cascadeOnDelete |
| `admin_id` | FK admins |
| `body` | text |
| `created_at` | timestamp |

### 5.3 `help_request_events`

سجل تدقيق بنفس شكل `hiring_request_events`: `help_request_id`، و`actor_user_id`، و`action`، و`data` (json)، و`created_at`.
الأحداث: `submitted`، و`assigned`، و`reassigned`، و`handling_started`، و`note_added`، و`closed`.

## 6. الحالات والانتقالات

```text
new ──(Start Handling)──▶ in_progress ──(Close Request + outcome)──▶ closed
 │                              ▲
 └──(Assign / Reassign)─────────┘ (يحدث في new أو in_progress دون تغيير الحالة)
```

- `Start Handling`: من `new` فقط. يعيّن الأدمن الحالي ضمنياً إذا لم يكن الطلب مُعيَّناً. أما إذا كان مُعيَّناً لأدمن آخر فلا يُسمح.
- `Close Request`: من `in_progress` فقط، وبواسطة الأدمن المُعيَّن.
- `Assign / Reassign`: في `new` و`in_progress`، ويحتاج صلاحية التعيين.
- `Add Note`: في أي حالة، بما فيها `closed`. لا تعديل ولا حذف.
- `closed` حالة نهائية، ولا يوجد Reopen في Figma.

## 7. الصلاحيات

Admin (`AdminPermissionEnum`):

- `admin.help-requests.view`: القائمة والتفاصيل والملاحظات.
- `admin.help-requests.assign`: التعيين والتعيين الجماعي وإعادة التعيين.
- `admin.help-requests.handle`: Start Handling، وClose، وAdd Note.

Client (`ClientPermissionEnum`):

- `client.help-requests.create`: إرسال الطلب، ويملكها الـOwner تلقائياً (القرار 4.5).

تُضاف عبر `PermissionSeeder` و`qader:bootstrap-access` بدون إعادة إنشاء القاعدة.

## 8. الـEndpoints (منفذة)

### Client

```text
GET  /api/v1/client/workspaces/{workspace}/help-requests/options
POST /api/v1/client/workspaces/{workspace}/help-requests
```

- `options` تُرجع `help_types` و`contact_methods` مع labels، و`defaults` (`contact_method=email`، و`contact_email` = بريد المستخدم).
- `store` تستقبل: `help_type`، و`description` (إلزامي مع `other`)، و`contact_method`، و`contact_email`، أو `contact_phone_country_code` + `contact_phone_number`.
- `store` تُرجع بيانات نافذة النجاح: `reference`، و`title`، و`help_type_label`، و`contact.method_label`، و`status`.
- العضو غير النشط أو غير العضو يأخذ `404`، والعضو بلا صلاحية يأخذ `403`.

### Admin

| Method | Path | الصلاحية |
| --- | --- | --- |
| GET | `/api/v1/admin/help-requests/options` | view |
| GET | `/api/v1/admin/help-requests` | view |
| GET | `/api/v1/admin/help-requests/assignees` | assign |
| POST | `/api/v1/admin/help-requests/assign` | assign |
| GET | `/api/v1/admin/help-requests/{helpRequest}` | view |
| POST | `/api/v1/admin/help-requests/{helpRequest}/reassign` | assign |
| POST | `/api/v1/admin/help-requests/{helpRequest}/start` | handle |
| POST | `/api/v1/admin/help-requests/{helpRequest}/notes` | handle |
| GET | `/api/v1/admin/help-requests/{helpRequest}/hiring-requests` | handle |
| POST | `/api/v1/admin/help-requests/{helpRequest}/close` | handle |

- **القائمة:** `scope=all|mine`، و`search` (Request ID أو اسم العميل أو اسم الـWorkspace)، و`status[]`، و`help_type[]`، و`assigned_admin_id`، و`created_from`، و`created_to`، و`per_page`. تُرجع `requests` + `meta` للترقيم.
- **التعيين الجماعي:** `{help_request_ids: [], admin_id}` للطلبات غير المُعيَّنة فقط، ويفشل كله لو كان أي طلب مُعيَّناً أو مغلقاً. من شاشة التفاصيل يُرسل طلب واحد في المصفوفة.
- **إعادة التعيين:** `{admin_id}` لطلب مُعيَّن وغير مغلق.
- **`assignees`:** الأدمن النشطون الذين يملكون `handle`، مع `full_name` و`email` و`avatar` و`role`، كما في نافذة Assigned Admin.
- **الإغلاق:** `{outcome, hiring_request_id?, details?}`.

### شكل `available_actions`

| Action | الشرط |
| --- | --- |
| `assign` | صلاحية assign، والطلب غير مُعيَّن وغير مغلق |
| `reassign` | صلاحية assign، والطلب مُعيَّن وغير مغلق |
| `start` | صلاحية handle، والحالة `new`، والطلب غير مُعيَّن أو مُعيَّن للأدمن الحالي |
| `close` | صلاحية handle، والحالة `in_progress`، والطلب مُعيَّن للأدمن الحالي |
| `add_note` | صلاحية handle، في أي حالة |

### حقول الـResource

مشتركة: `id`، و`reference`، و`title`، و`help_type`، و`help_type_label`، و`description`، و`contact {method, method_label, value, email, phone_country_code, phone_number}`، و`status`، و`status_label`، و`workspace {id, name}`، و`created_at`، و`updated_at`.

للأدمن فقط: `client {id, full_name, email}`، و`assigned_admin`، و`started_at`، و`closure {outcome, outcome_label, details, hiring_request {id, reference}, closed_by, closed_at}`، و`notes_count`، و`notes[] {id, body, author, created_at}`، و`available_actions`.

## 9. التحقق والقواعد

- `help_type`: من الـEnum. `description`: إلزامي مع `other`، وبحد أقصى 2000 حرف.
- `contact_method=email` يشترط `contact_email` بريداً صالحاً. و`whatsapp` يشترط رقماً صالحاً.
- الإغلاق: `outcome` إلزامي.
  - `new_hiring_request_created`: يشترط `hiring_request_id` تابعاً لنفس الـWorkspace، و`details` اختياري.
  - `client_request_clarified` و`issue_problem`: يشترطان `details`.
- كل الانتقالات داخل Transaction مع `lockForUpdate`، ويُسجل الحدث في `help_request_events`.

## 10. حالة التنفيذ

| الخطوة | الحالة |
| --- | --- |
| Migration `2026_11_09_010000_create_help_requests_tables` للجداول الثلاثة | مكتوبة، ولم تُنفَّذ بعد على أي قاعدة |
| Enums وModels (`app/Modules/V1/HelpRequests/Domain`) | منفذة |
| الصلاحيات في `AdminPermissionEnum` و`ClientPermissionEnum` وترجماتها | منفذة، وتُزامن عبر `PermissionSeeder` |
| خدمة العميل وخدمة الأدمن (`Application/Services`) | منفذة |
| Requests وResources وControllers | منفذة |
| Routes (`routes/v1/client/help-requests.php` و`routes/v1/admin/help-requests.php`) وتسجيلها في `config/modules.php` | منفذة |
| الترجمات: `lang/*/help_requests.php`، و`enums.help_request_*` | منفذة |
| أسماء Apidog (`EndpointNames`) والمجلد (`PortalMetadata`) | منفذة |
| Feature tests | متبقية |
| أمثلة `@response` لكل حالة، وتوليد OpenAPI | متبقية |

### للتشغيل

```bash
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan migrate
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=PermissionSeeder
docker compose -p qader-backend-local -f compose.yaml -f compose.local.yaml exec app php artisan db:seed --class=AdminRoleSeeder
```

دور `admin` الافتراضي يأخذ صلاحيات `admin.help-requests.*` تلقائياً عند إعادة تشغيل `AdminRoleSeeder`، لأنه يأخذ كل صلاحيات الأدمن عدا إدارة الصلاحيات والأدمنز.
