# خطة نظام الإشعارات

> الحالة: منفذة (تحتاج اختبار يدوي للتدفقات على سيرفر التست).

## 1. القرارات

| # | القرار |
|---|---|
| 1 | مستلمو العميل: كل أعضاء الـworkspace النشطين أصحاب صلاحية `client.hiring-requests.manage` (يشمل المالك). |
| 2 | الأدمن: يصله الإشعار فقط لو الطلب مُسنَد له؛ إشعار الإسناد نفسه يصل للأدمن المُسنَد. |
| 3 | كل الأحداث تُرسل Push (FCM) بالإضافة لقائمة الإشعارات. |
| 4 | لغة الـPush والإيميل: آخر لغة استخدمها المستخدم (`users.locale` من `Accept-Language`)، والافتراضي عربي. |
| 5 | إيقاف حساب العميل وإعادة تفعيله: إيميل. |
| 6 | الإرسال عبر الـqueue؛ الـcron على السيرفر يشغّل الـworker والـscheduler. |
| 7 | التذكيرات: قبل الموعد بساعة وعند الموعد. |
| 8 | الإضافة والحذف: إجراءات الأدمنز تصل للسوبر أدمنز، وإجراءات أعضاء الفريق تصل لمالك الـworkspace. |
| 9 | "الإضافة" تشمل كل السجلات المسجلة في Trash Registry (يشمل البيانات المرجعية). |

## 2. شكل التخزين

```json
{
  "type": "hiring_offer_received",
  "title": "notifications.hiring_offer_received.title",
  "body": "notifications.hiring_offer_received.body",
  "params": { "company": "Qader Technology", "job_title": "Backend Developer" },
  "target_id": 9
}
```

- `title` و`body` مفاتيح ترجمة، تُترجم عند القراءة حسب `Accept-Language`.
- `workspace_id` يُضاف فقط في إشعارات العميل (شاشات العميل تحت `/client/workspaces/{workspace}`).
- `translated_params` يظهر فقط عندما يكون أحد الـparams نفسه يحتاج ترجمة (نوع السجل، القرار، نوع طلب المساعدة).
- عمود `type` في جدول `notifications` يخزن القيمة القصيرة.

### Response الـAPI
Endpoints:
- الموهبة والأدمن: `/api/v1/{talent|admin}/notifications` (+ `/unread-count`, `/types`, `PATCH /read-all`, `PATCH /{id}/read`).
- العميل لكل workspace: `/api/v1/client/{workspace}/notifications` (+ `/unread-count`, `/types`, `PATCH /read-all`, `PATCH /{id}/read`) — يتطلب أن يكون العميل عضوًا نشطًا في الـworkspace، ويرجع إشعارات هذا الـworkspace فقط.

الـresponse: `data.notifications` و`data.meta`. و`GET .../notifications/types` يرجع `data.types`: كل الأنواع الممكنة لهذه البوابة `[{value, label}]`.

كل إشعار يرجع: `id`, `type`, `title`, `body` (مترجمة), `target_id`, `workspace_id`, `params`, `read_at`, `created_at`.

### FCM
`notification.title/body` مترجمة بلغة المستخدم، و`data`: `notification_id`, `type`, `target_id`, `workspace_id` (كلها strings).

## 3. التصميم

- **نقاط الربط:** كل workflow يكتب أصلًا سجل حدث (`hiring_request_events`, `talent_application_events`, `help_request_events`, `client_management_events`, `trash_events`). الإشعارات مربوطة على إنشاء هذه السجلات في `NotificationModuleServiceProvider`، فلا يحتاج أي workflow أن يتذكر الإرسال.
- **الإضافة:** listener على `created` لكل models الـTrash Registry، يعمل فقط لو القائم بالفعل أدمن أو عميل.
- **إجراءات الموهبة على مواعيد المقابلة** (اختيار/رفض/إلغاء) لا تكتب حدثًا، فتُرسل صراحة من `TalentInterviewWorkflow`.
- **التنفيذ:** `DispatchDomainNotification` (queued, after commit) يحدد المستلمين، ثم `PlatformNotification` (queued, after commit) على قناتي `database` و`FcmChannel`.
- لا يُرسل إشعار للقائم بالفعل، ولا لمستخدم غير نشط.
- `FirebaseCloudMessaging` يعيد استخدام الـaccess token (cache) بدل إنشائه مع كل رسالة؛ فشل جهاز واحد يُسجَّل في الـlog ولا يُفشل الإشعار.

## 4. الأحداث

### طلبات التعيين
| الحدث | المستلمون | type |
|---|---|---|
| `submitted` | أعضاء الـworkspace أصحاب الصلاحية | `hiring_request_submitted` |
| `assigned` / `reassigned` | الأدمن المُسنَد | `hiring_request_assigned` |
| `offer_sent` | الموهبة | `hiring_offer_received` |
| `offer_accepted` / `offer_rejected` / `offer_countered` | العميل + الأدمن | `hiring_offer_accepted` / `hiring_offer_rejected` / `hiring_counter_offer_received` |
| `counter_offer_accepted` | الموهبة + العميل + الأدمن | `hiring_counter_offer_accepted` |
| `offer_responded_on_behalf` | الموهبة + العميل | `hiring_offer_responded_on_behalf` |
| `offer_response_deadline_expired` | الموهبة + العميل + الأدمن | `hiring_offer_expired` |
| `interview_proposed` / `interview_rescheduled` | الموهبة + العميل | `hiring_interview_proposed` / `hiring_interview_rescheduled` |
| `interview_schedule_responded` | الأدمن | `hiring_interview_response_received` |
| `interview_confirmed` | الموهبة + العميل | `hiring_interview_confirmed` |
| `interview_completed` | العميل | `hiring_decision_required` |
| `decision_recorded` (+ `_on_behalf`) | الموهبة + العميل (+ الأدمن) | `hiring_decision_made` |
| `decision_withdrawn` | العميل + الأدمن | `hiring_decision_withdrawn` |
| `item_held` / `item_resumed` / `item_cancelled` | الموهبة + العميل + الأدمن | `hiring_item_on_hold` / `hiring_item_resumed` / `hiring_item_cancelled` |

الإيقاف بسبب تعطيل الـworkspace يُبلَّغ للموهبة فقط؛ المالك يصله `workspace_deactivated`.

### طلبات الانضمام
| الحدث | المستلمون | type |
|---|---|---|
| `reviewer_assigned` / `reviewer_reassigned` | المراجع | `talent_application_assigned` |
| `interview_scheduled` / `replacement_interview_slots_offered` | الموهبة | `talent_interview_slots_available` |
| الموهبة اختارت موعدًا | المراجع | `talent_interview_slot_selected` |
| الموهبة رفضت المواعيد | المراجع | `talent_interview_slots_rejected` |
| الموهبة ألغت المقابلة المؤكدة | المراجع | `talent_interview_cancelled` |
| `application_accepted` / `application_rejected` | الموهبة | `talent_application_accepted` / `talent_application_rejected` |

### طلبات المساعدة
| الحدث | المستلمون | type |
|---|---|---|
| `assigned` / `reassigned` | الأدمن المُسنَد | `help_request_assigned` |
| `handling_started` | العميل صاحب الطلب | `help_request_in_progress` |
| `closed` | العميل صاحب الطلب | `help_request_closed` |

### إدارة العملاء
| الحدث | المستلم | القناة |
|---|---|---|
| تعطيل / تفعيل workspace | مالك الـworkspace | `workspace_deactivated` / `workspace_activated` |
| إيقاف / إعادة تفعيل حساب العميل | العميل | إيميل `ClientAccountStatusMail` (مع السبب عند الإيقاف) |

### الإضافة والحذف
| الحدث | type |
|---|---|
| إضافة سجل | `record_created` |
| حذف (soft delete) | `record_deleted` |
| استرجاع من المحذوفات | `record_restored` |
| حذف نهائي من المحذوفات | `record_removed` |

المستلم: السوبر أدمنز لو القائم أدمن، أو مالك الـworkspace لو القائم عضو في الفريق. الـparams: `actor`, `name`، و`record` (اسم نوع السجل مترجم).

### التذكيرات (كل دقيقة عبر الـscheduler)
| التذكير | المستلمون | type |
|---|---|---|
| مقابلة تعيين مؤكدة: قبل ساعة / عند البدء | الموهبة + العميل + الأدمن | `hiring_interview_reminder` / `hiring_interview_starting` |
| مقابلة طلب انضمام: قبل ساعة / عند البدء | الموهبة + المراجع + أدمنز المقابلة | `talent_interview_reminder` / `talent_interview_starting` |
| مهلة الرد على العرض: قبل ساعة | الطرف المطلوب منه الرد | `hiring_offer_deadline_reminder` |
| مهلة قرار العميل: قبل ساعة | أعضاء الـworkspace أصحاب الصلاحية | `hiring_decision_deadline_reminder` |

كل تذكير يُسجل مرة واحدة في `notification_reminders` بمفتاح يتضمن وقت الموعد، فتغيير الموعد ينتج تذكيرات جديدة والقديمة لا تُرسل.

## 5. التنقل من الإشعار
الفرونت يحدد الصفحة من `type` (مع البوابة)، ويفتح السجل `target_id`:
- إشعارات `hiring_*` للموهبة: `target_id` = hiring request item. للعميل والأدمن: `target_id` = hiring request.
- `talent_*`: `target_id` = talent application.
- `help_request_*`: `target_id` = help request.
- `workspace_*`: `target_id` = workspace.
- `record_*`: `target_id` = السجل، ونوعه في `params.resource` (مفتاح Trash Registry مثل `skills`). `record_deleted` و`record_removed` يفتحان صفحة المحذوفات.
- إشعارات العميل كلها فيها `workspace_id`.

## 6. التأثير والتشغيل
- **Database:** migration لعمود `users.locale`، و migration لجدول `notification_reminders`.
- **Endpoints:** إشعارات العميل انتقلت إلى `/client/{workspace}/notifications`، وأُضيف `GET .../notifications/types` لكل بوابة.
- **السيرفر:** `php artisan migrate`، والـcron يشغّل `schedule:run` كل دقيقة و`queue:work`. الـqueue الافتراضية مستخدمة (`NOTIFICATIONS_QUEUE` اختياري).
- **FCM:** يحتاج `FIREBASE_CREDENTIALS`؛ بدونه تُحفظ الإشعارات في القائمة فقط بدون Push.
