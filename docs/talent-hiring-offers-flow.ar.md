# دليل عروض التوظيف عند الموهبة

هذا الملف يشرح السلوك الحالي لواجهات عروض التوظيف في بوابة الموهبة، والحالات التي تظهر في الـResponse، والفلاتر، وتغير `available_actions` خلال دورة العرض والمقابلة.

## 1. الواجهات الأساسية

```text
GET  /api/v1/talent/hiring-offers/options
GET  /api/v1/talent/hiring-offers
GET  /api/v1/talent/hiring-offers/{item}
POST /api/v1/talent/hiring-offers/{item}/accept
POST /api/v1/talent/hiring-offers/{item}/reject
POST /api/v1/talent/hiring-offers/{item}/counter

GET  /api/v1/talent/hiring-interviews/{interview}
POST /api/v1/talent/hiring-interviews/{interview}/accept-schedule
POST /api/v1/talent/hiring-interviews/{interview}/request-schedule-change
POST /api/v1/talent/hiring-interviews/{interview}/apologize
```

`{item}` هو `hiring_request_items.id` وليس `hiring_offers.id`.

القائمة والتفاصيل لا تعرضان إلا عناصر الموهبة الحالية. كما لا يظهر العنصر في القائمة قبل إرسال أول عرض، لأن الاستعلام يشترط وجود عرض له `sent_at`.

## 2. الفرق بين حالة العنصر وحالة العرض

يوجد مستويان منفصلان للحالة:

- `data.items[*].status`: حالة عنصر طلب التعيين بالكامل.
- `data.items[*].offer.status`: حالة أحدث عرض داخل العنصر.
- `data.items[*].offers`: تاريخ جميع العروض؛ لذلك قد يحتوي حالات قديمة مثل `countered`.

لا يجب استخدام `offer.status` بدل `item.status` أو العكس، لأن لكل منهما دورة مختلفة.

## 3. حالات عنصر طلب التعيين

| الحالة | معناها للموهبة | الأكشنز على العنصر |
|---|---|---|
| `offer` | توجد مرحلة عرض أو تفاوض جارية | تعتمد على أحدث عرض كما هو موضح لاحقًا |
| `interview` | تم قبول العرض المبدئي والمقابلة مطلوبة أو ما زال القرار بعدها جاريًا | `view` فقط على عنصر العرض، وأكشنز الموعد ترجع داخل `interview.available_actions` |
| `accepted` | تم قبول الموهبة في هذا العنصر | `view` |
| `on_hold` | أوقف العميل أو الأدمن العنصر مؤقتًا | `view` |
| `rejected` | رُفض العرض من الموهبة أو كان قرار العميل النهائي بالرفض | `view` |
| `cancelled` | ألغى العميل أو الأدمن العنصر | `view` |
| `completed` | حالة نهائية مخصصة لإتمام دورة العنصر | `view`؛ ولا يوجد أكشن للموهبة ينقل العنصر إليها |

حالة `pending` موجودة داخليًا عند إنشاء الطلب، لكنها لا ترجع في Talent Options ولا تظهر طبيعيًا في قائمة الموهبة؛ لأن العرض لم يُرسل بعد.

## 4. حالات العرض

| الحالة | معناها | هل قد تكون أحدث عرض؟ |
|---|---|---|
| `pending` | العرض ينتظر رد الطرف الآخر | نعم |
| `accepted` | تم قبول العرض | نعم |
| `rejected` | تم رفض العرض | نعم |
| `expired` | انتهت مهلة رد الموهبة على عرض العميل | نعم |
| `countered` | استُبدل العرض بعرض مقابل أحدث | تظهر في `offers` التاريخية، وليست فلترًا لأحدث عرض |

فلتر `offer_status[]` يعمل على `latestOffer` فقط، وليس على أي عرض قديم داخل `offers`.

## 5. Response الـOptions

`GET /api/v1/talent/hiring-offers/options` يعيد الخيارات الصالحة لشاشة الموهبة:

```json
{
  "success": true,
  "data": {
    "item_statuses": [
      { "value": "offer", "label": "Offer" },
      { "value": "interview", "label": "Interview" },
      { "value": "accepted", "label": "Accepted" },
      { "value": "on_hold", "label": "On Hold" },
      { "value": "rejected", "label": "Rejected" },
      { "value": "cancelled", "label": "Cancelled" },
      { "value": "completed", "label": "Completed" }
    ],
    "offer_statuses": [
      { "value": "pending", "label": "Pending" },
      { "value": "accepted", "label": "Accepted" },
      { "value": "rejected", "label": "Rejected" },
      { "value": "expired", "label": "Expired" }
    ],
    "sort_options": [
      { "value": "newest", "label": "Newest" },
      { "value": "oldest", "label": "Oldest" }
    ]
  }
}
```

تتغير `label` حسب `Accept-Language`، بينما تظل `value` ثابتة.

## 6. فلاتر قائمة العروض

`GET /api/v1/talent/hiring-offers` يدعم:

| البارامتر | النوع | الاستخدام |
|---|---|---|
| `status[]` | Array | فلترة حالة عنصر التعيين بقيم `item_statuses` |
| `offer_status[]` | Array | فلترة حالة أحدث عرض بقيم `offer_statuses` |
| `search` | String | البحث بالمرجع أو المسمى الوظيفي أو اسم الشركة |
| `sort` | String | `newest` افتراضيًا أو `oldest` |
| `per_page` | Integer | من 1 إلى 100، والقيمة الافتراضية 15 |

مثال:

```http
GET /api/v1/talent/hiring-offers?status[]=offer&offer_status[]=pending&sort=newest&search=Backend&per_page=15
```

يمكن إرسال أكثر من قيمة لكل فلتر:

```http
GET /api/v1/talent/hiring-offers?status[]=accepted&status[]=rejected&offer_status[]=accepted&offer_status[]=rejected
```

## 7. قواعد `available_actions` على عنصر العرض

يرجع العنصر دائمًا:

```json
"available_actions": ["view"]
```

وتضاف أكشنز الرد الثلاثة فقط عندما تتحقق الشروط معًا:

```text
item.status = offer
latest offer.status = pending
latest offer.proposed_by = client
```

عندها يكون الريسبونس:

```json
"available_actions": [
  "view",
  "accept_offer",
  "reject_offer",
  "counter_offer"
]
```

لا يعتمد الفرونت على الحالة وحدها؛ يجب أن يعتمد على `available_actions` لتحديد الأزرار الظاهرة.

## 8. انتقالات العرض وتغير الأكشنز

### 8.1 إرسال العرض الأول من العميل

عندما يبدأ الأدمن طلب التعيين:

```text
item.status: pending -> offer
offer.status: pending
offer.proposed_by: client
```

أكشنز الموهبة:

```json
["view", "accept_offer", "reject_offer", "counter_offer"]
```

### 8.2 قبول الموهبة للعرض

```http
POST /api/v1/talent/hiring-offers/{item}/accept
```

النتيجة:

- يتحول أحدث عرض إلى `accepted`.
- إذا كان `interview_required = true` يتحول العنصر إلى `interview`.
- إذا كان `interview_required = false` يتحول العنصر مباشرة إلى `accepted`.
- تصبح أكشنز عنصر العرض `view` فقط.

### 8.3 رفض الموهبة للعرض

```http
POST /api/v1/talent/hiring-offers/{item}/reject
```

النتيجة:

```text
offer.status -> rejected
item.status  -> rejected
available_actions -> [view]
```

### 8.4 إرسال Counter Offer من الموهبة

```http
POST /api/v1/talent/hiring-offers/{item}/counter
```

النتيجة:

- يتحول عرض العميل السابق إلى `countered`.
- ينشأ عرض جديد `proposed_by = talent` و`status = pending`.
- يظل العنصر في `offer`.
- تصبح أكشنز الموهبة `view` فقط حتى يرد الأدمن.

مثال الحالة المنتظرة:

```json
{
  "status": "offer",
  "offer": {
    "proposed_by": "talent",
    "status": "pending"
  },
  "available_actions": ["view"]
}
```

### 8.5 رد الأدمن بعرض معدل

```http
POST /api/v1/admin/hiring-requests/items/{item}/offers
```

النتيجة:

- يتحول Counter الموهبة السابق إلى `countered`.
- ينشأ عرض جديد من العميل بحالة `pending`.
- تعود أكشنز الموهبة الثلاثة للقبول والرفض والـCounter.

### 8.6 قبول الأدمن لـCounter الموهبة

```http
POST /api/v1/admin/hiring-requests/items/{item}/offers/accept-counter
```

النتيجة:

- يتحول Counter الموهبة إلى `accepted`.
- ينتقل العنصر إلى `interview` أو `accepted` حسب `interview_required`.
- تظل أكشنز عنصر العرض عند الموهبة `view` فقط.

### 8.7 انتهاء مهلة عرض العميل

عند انتهاء `response_due_at`:

```text
offer.status -> expired
item.status  -> offer
available_actions -> [view]
```

لا تستطيع الموهبة الرد على العرض المنتهي. يستطيع الأدمن إرسال عرض جديد بمهلة جديدة أو تسجيل الرد بالنيابة وفق صلاحياته.

يُحتسب عدم الرد على الموهبة، وقد يُعطّل حسابها بعد الوصول للحد الموجود في إعدادات المنصة. انتهاء العرض لا يغلق عنصر التعيين تلقائيًا.

### 8.8 تعليق العنصر واستئنافه

عند التعليق:

```text
item.status -> on_hold
available_actions -> [view]
```

- تلغى المقابلة النشطة إن وجدت.
- تتوقف مهلة العرض أو القرار الحالية.

عند الاستئناف يعود العنصر إلى حالته السابقة. وإذا عاد إلى `offer` مع عرض `pending` من العميل، تبدأ مهلة جديدة وتعود أكشنز الرد للموهبة.

### 8.9 إلغاء العنصر

عند إلغاء العميل أو الأدمن:

```text
item.status -> cancelled
available_actions -> [view]
```

وتلغى المقابلة النشطة المرتبطة به إن وجدت.

## 9. أكشنز المقابلة عند الموهبة

أكشنز المقابلة ترجع داخل:

```json
"interview": {
  "available_actions": []
}
```

وهي مستقلة عن `available_actions` الموجودة على عنصر العرض.

| حالة المقابلة | حالة رد الموهبة | الأكشنز |
|---|---|---|
| `awaiting_responses` | `pending` | `accept_schedule`, `request_schedule_change`, `apologize` |
| `awaiting_responses` | ردت الموهبة بالفعل | لا توجد أكشنز حتى رد الطرف الآخر أو الأدمن |
| `awaiting_admin_confirmation` | أي قيمة | لا توجد أكشنز؛ انتظار تأكيد الأدمن |
| `awaiting_reschedule` | أي قيمة | لا توجد أكشنز؛ انتظار موعد جديد من الأدمن |
| `confirmed` وقبل البداية | أي قيمة | `request_schedule_change`, `apologize` |
| `confirmed` أو `in_progress` وداخل وقت الموعد | أي قيمة | يعتمد زر الدخول على `can_join = true` وليس على Action باسم `join` |
| `completed` أو `cancelled` | أي قيمة | لا توجد أكشنز |

إذا طلبت الموهبة تغيير الموعد أو اعتذرت، يجب إرسال `reason`، وتنتقل المقابلة إلى `awaiting_reschedule` حتى يحدد الأدمن موعدًا جديدًا.

## 10. ما بعد المقابلة

بعد اكتمال المقابلة يظل `item.status = interview` حتى يسجل العميل أو الأدمن القرار النهائي:

- قرار الاستمرار يحول العنصر إلى `accepted`.
- قرار الرفض يحول العنصر إلى `rejected`.

لا توجد Endpoint للموهبة لتسجيل هذا القرار النهائي؛ الموهبة ترى النتيجة من خلال حالة العنصر.

## 11. قواعد تنفيذ الفرونت

1. استخدم `GET /hiring-offers/options` لبناء الفلاتر، ولا تكتب القيم أو الترجمات داخل الفرونت.
2. استخدم `item.status` لعرض المرحلة العامة و`offer.status` لعرض حالة أحدث عرض.
3. اعرض الأزرار طبقًا لـ`available_actions` فقط.
4. استخدم `offers` لعرض تاريخ التفاوض، و`offer` لأحدث عرض قابل للعرض أو الإجراء.
5. بعد أي Accept أوReject أوCounter أعد استبدال العنصر بالـResponse الجديد بدل تعديل الحالة محليًا.
6. أكشنز المقابلة تؤخذ من `interview.available_actions` وليس من أكشنز العنصر.
7. استخدم `can_join` لإظهار زر دخول المقابلة.
8. عند `409` أعد جلب تفاصيل العنصر؛ غالبًا تغيرت الحالة أو انتهت المهلة في طلب آخر.

