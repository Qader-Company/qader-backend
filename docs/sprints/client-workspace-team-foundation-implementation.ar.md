# تنفيذ تأسيس Client Portal — مساحات العمل والفريق والصلاحيات

تاريخ التنفيذ: 25 أكتوبر 2026  
المرجع: [الخطة المعتمدة](client-workspace-team-foundation-plan.ar.md)

## ما تم تنفيذه

- Master Data مستقلة للصناعات بترجمات عربية وإنجليزية وإدارة من Admin Portal.
- إنشاء وتعديل وعرض واختيار أكثر من Workspace لحساب Client واحد.
- رفع شعار Workspace وحفظ روابط التواصل بمنصات معرفة في Enum.
- إنشاء عضو المالك ودور `owner` المحمي تلقائيًا داخل transaction إنشاء المساحة.
- صلاحيات Client مستقلة، مع وصول `owner` لكل الصلاحيات الحالية والمستقبلية داخل مساحته.
- أدوار محلية لكل Workspace مع أكثر من دور للعضو وحماية كاملة من عبور بيانات مساحة أخرى.
- إنشاء حساب الموظف وعضويته مباشرة بأدوار متعددة، وإرسال بيانات الدخول المؤقتة بالبريد للحساب الجديد أو إشعار الحساب الموجود.
- حالتا العضوية `active` و`suspended`، مع Soft Delete عند إزالة العضو.
- Dashboard بعقد response ثابت، ويعيد أصفارًا للموديولات التي لم تنفذ بعد.
- Seeder آمن للصناعات واختبار متكامل لتدفق إنشاء المساحة وإضافة الموظف.

## الجداول

تضيف migration `2026_10_31_000000_create_client_workspace_tables.php` الجداول:

- `industries`, `industry_translations`
- `workspaces`, `workspace_social_links`
- `workspace_members`, `workspace_roles`
- `workspace_role_permissions`, `workspace_member_roles`

وتضيف migration التحديثية `2026_11_02_000000_upgrade_talent_and_workspace_industries.php`:

- `talent_industries`
- `workspace_industries`
- `workspace_professional_fields`

كما تنقل `workspaces.industry_id` القديمة إلى `workspace_industries` قبل حذف العمود المفرد.

## Endpoints

### Admin / Industries

- `GET /api/v1/admin/industries`
- `POST /api/v1/admin/industries`
- `PATCH /api/v1/admin/industries/{industry}`

الصلاحيات: `admin.industries.view`, `admin.industries.manage`.

### Client / Master Data and Options

- `GET /api/v1/client/master-data/countries`
- `GET /api/v1/client/master-data/cities?country_id={country_id}`
- `GET /api/v1/client/master-data/industries`
- `GET /api/v1/client/master-data/professional-fields`
- `GET /api/v1/client/workspaces/options`

### Client / Workspaces and Dashboard

- `GET|POST /api/v1/client/workspaces`
- `GET|PATCH /api/v1/client/workspaces/{workspace}`
- `GET /api/v1/client/workspaces/{workspace}/dashboard`

إنشاء وتعديل Workspace يدعمان `multipart/form-data`. مثال للروابط:

```json
{
  "name": "Qader Technology",
  "industry_ids": [1, 2],
  "professional_field_ids": [1, 2],
  "description": "Technology company building digital products.",
  "is_company": true,
  "country_id": 1,
  "city_id": 1,
  "size_band": "11-50",
  "website_url": "https://qader.example.com",
  "social_links": [
    {"platform": "linkedin", "url": "https://linkedin.com/company/qader"},
    {"platform": "x", "url": "https://x.com/qader"}
  ]
}
```

قيمة `social_links.*.url` تقبل روابط `http` و`https` مع protocol اختياري، وتدعم subdomains والـpath والـquery والـhash. أما أسماء الحسابات المنفردة والنصوص العشوائية فلا تقبل. يظل `website_url` مطلوبًا بصيغة URL كاملة صحيحة عند إرساله.

### Client / Roles and Permissions

- `GET /api/v1/client/access-control/workspaces/{workspace}/permissions`
- `GET|POST /api/v1/client/access-control/workspaces/{workspace}/roles`
- `GET|PATCH|DELETE /api/v1/client/access-control/workspaces/{workspace}/roles/{role}`
- `PATCH /api/v1/client/access-control/workspaces/{workspace}/roles/{role}/status`

```json
{
  "name_en": "Hiring Manager",
  "name_ar": "مدير التوظيف",
  "is_active": true,
  "permissions": [
    "client.workspace.view",
    "client.dashboard.view",
    "client.members.view"
  ]
}
```

### Client / Members

- `GET|POST /api/v1/client/workspaces/{workspace}/members`
- `GET|PATCH|DELETE /api/v1/client/workspaces/{workspace}/members/{member}`

```json
{
  "first_name": "Team",
  "last_name": "Member",
  "email": "team.member@example.com",
  "role_ids": [2, 3]
}
```

إذا لم يكن البريد مسجلًا كـClient ينشئ النظام كلمة مرور مؤقتة، يفعّل `must_change_password`، ويرسل بيانات الدخول إلى البريد. الحساب الموجود يربط بالمساحة نفسها بدون إنشاء حساب مكرر.

## تحديث السيرفر

بعد رفع الكود، نفذ بالترتيب:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=AdminRoleSeeder --force
php artisan db:seed --class=IndustrySeeder --force
php artisan scribe:generate
```

تشغيل `AdminRoleSeeder` يزامن جميع الصلاحيات، بما فيها صلاحيات Client، قبل تحديث أدوار الأدمن المحفوظة.

## تحديث Apidog

- Client: `http://localhost:8000/docs/client.openapi`
- Admin: `http://localhost:8000/docs/admin.openapi`

الملفات المولدة تحتوي `Accept: application/json` و`X-Authorization: {{api_key}}` و`Authorization: Bearer {{access_token}}` للـendpoints المحمية.

## التحقق

- PHP lint: ناجح لكل الملفات الجديدة.
- جميع اختبارات المشروع الحالية: ناجحة.
- اختبار `ClientWorkspaceFlowTest`: إنشاء Workspace، الحفاظ على توافق الدولة والمدينة، إنشاء دورين، إنشاء حساب موظف بكلمة مرور مؤقتة، إرسال بيانات الدخول، وإسناد الدورين بنجاح.
- توليد Scribe وملفات Client/Admin OpenAPI: ناجح.
