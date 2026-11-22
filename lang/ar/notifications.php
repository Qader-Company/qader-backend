<?php

$t = fn (string $title, string $body): array => ['title' => $title, 'body' => $body];

return [
    'marked_as_read' => 'تم تحديد الإشعار كمقروء بنجاح.',
    'all_marked_as_read' => 'تم تحديد جميع الإشعارات كمقروءة بنجاح.',

    // طلبات التعيين
    'hiring_request_submitted' => $t('طلب تعيين جديد', 'تم إرسال طلب تعيين جديد :reference لـ :company.'),
    'hiring_request_assigned' => $t('تم إسناد طلب تعيين لك', 'تم إسناد طلب التعيين :reference من :company إليك.'),
    'hiring_offer_received' => $t('عرض عمل جديد', 'وصلك عرض عمل من :company لوظيفة :job_title.'),
    'hiring_offer_accepted' => $t('تم قبول العرض', 'وافقت الموهبة :talent_code على عرض :job_title (:reference).'),
    'hiring_offer_rejected' => $t('تم رفض العرض', 'رفضت الموهبة :talent_code عرض :job_title (:reference).'),
    'hiring_counter_offer_received' => $t('عرض مضاد جديد', 'أرسلت الموهبة :talent_code عرضًا مضادًا لوظيفة :job_title (:reference).'),
    'hiring_counter_offer_accepted' => $t('تم قبول العرض المضاد', 'وافقت :company على العرض المضاد لوظيفة :job_title (:reference).'),
    'hiring_offer_responded_on_behalf' => $t('تم تسجيل الرد على العرض', 'سجّل فريق قادر الرد ":decision" على عرض :job_title (:reference).'),
    'hiring_offer_expired' => $t('انتهت مهلة العرض', 'انتهت مهلة الرد على عرض :job_title (:reference).'),
    'hiring_offer_deadline_reminder' => $t('مهلة الرد على العرض تنتهي قريبًا', 'متبقي أقل من ساعة للرد على عرض :job_title (:reference).'),
    'hiring_interview_proposed' => $t('تم اقتراح موعد مقابلة', 'تم اقتراح موعد مقابلة لوظيفة :job_title (:reference). من فضلك أكّد ردك.'),
    'hiring_interview_rescheduled' => $t('تم تغيير موعد المقابلة', 'تم اقتراح موعد جديد لمقابلة :job_title (:reference). من فضلك أكّد ردك.'),
    'hiring_interview_response_received' => $t('رد على موعد المقابلة', 'وصل رد ":response" على موعد مقابلة :job_title (:reference).'),
    'hiring_interview_confirmed' => $t('تم تأكيد المقابلة', 'تم تأكيد موعد مقابلة :job_title (:reference).'),
    'hiring_interview_reminder' => $t('المقابلة بعد ساعة', 'مقابلة :job_title (:reference) تبدأ بعد ساعة.'),
    'hiring_interview_starting' => $t('المقابلة تبدأ الآن', 'مقابلة :job_title (:reference) تبدأ الآن.'),
    'hiring_decision_required' => $t('مطلوب قرار التعيين', 'انتهت مقابلة :job_title (:reference). من فضلك أرسل قرارك.'),
    'hiring_decision_deadline_reminder' => $t('مهلة قرار التعيين تنتهي قريبًا', 'متبقي أقل من ساعة لإرسال قرارك في :job_title (:reference).'),
    'hiring_decision_made' => $t('قرار التعيين', 'قرار التعيين في :job_title (:reference) هو ":decision".'),
    'hiring_decision_withdrawn' => $t('تم سحب قرار التعيين', 'تم سحب قرار التعيين في :job_title (:reference).'),
    'hiring_item_on_hold' => $t('تم إيقاف التعيين مؤقتًا', 'تم إيقاف التعيين في :job_title (:reference) مؤقتًا.'),
    'hiring_item_resumed' => $t('تم استئناف التعيين', 'تم استئناف التعيين في :job_title (:reference).'),
    'hiring_item_cancelled' => $t('تم إلغاء التعيين', 'تم إلغاء التعيين في :job_title (:reference).'),

    // طلبات الانضمام
    'talent_application_assigned' => $t('تم إسناد طلب انضمام لك', 'تم إسناد طلب انضمام :talent (:talent_code) إليك للمراجعة.'),
    'talent_interview_slots_available' => $t('اختر موعد مقابلتك', 'تم إرسال مواعيد مقابلة لطلبك. من فضلك اختر موعدًا.'),
    'talent_interview_slot_selected' => $t('تم اختيار موعد المقابلة', 'اختار :talent (:talent_code) موعد المقابلة.'),
    'talent_interview_slots_rejected' => $t('تم رفض مواعيد المقابلة', 'رفض :talent (:talent_code) المواعيد المقترحة ويحتاج مواعيد جديدة.'),
    'talent_interview_cancelled' => $t('الموهبة ألغت المقابلة', 'ألغى :talent (:talent_code) المقابلة المؤكدة ويحتاج مواعيد جديدة.'),
    'talent_interview_reminder' => $t('المقابلة بعد ساعة', 'مقابلة طلب الانضمام :talent_code تبدأ بعد ساعة.'),
    'talent_interview_starting' => $t('المقابلة تبدأ الآن', 'مقابلة طلب الانضمام :talent_code تبدأ الآن.'),
    'talent_application_accepted' => $t('تم قبول طلبك', 'مبروك! تم قبول طلب انضمامك.'),
    'talent_application_rejected' => $t('نتيجة طلب الانضمام', 'نعتذر، لم يتم قبول طلب انضمامك هذه المرة.'),

    // طلبات المساعدة
    'help_request_assigned' => $t('تم إسناد طلب مساعدة لك', 'تم إسناد طلب المساعدة :reference (:help_type) إليك.'),
    'help_request_in_progress' => $t('جاري العمل على طلب المساعدة', 'بدأ فريق قادر العمل على طلب المساعدة :reference.'),
    'help_request_closed' => $t('تم إغلاق طلب المساعدة', 'تم إغلاق طلب المساعدة :reference.'),

    // مساحات العمل
    'workspace_deactivated' => $t('تم تعطيل مساحة العمل', 'تم تعطيل مساحة العمل :workspace وإيقاف نشاط التعيين مؤقتًا.'),
    'workspace_activated' => $t('تم تفعيل مساحة العمل', 'تم تفعيل مساحة العمل :workspace مرة أخرى.'),

    // الإضافة والحذف (الأدمنز ← السوبر أدمن، أعضاء الفريق ← مالك مساحة العمل)
    'record_created' => $t('إضافة :record', 'أضاف :actor :record ":name".'),
    'record_deleted' => $t('حذف :record', 'حذف :actor :record ":name".'),
    'record_restored' => $t('استرجاع :record', 'استرجع :actor :record ":name" من المحذوفات.'),
    'record_removed' => $t('حذف :record نهائيًا', 'حذف :actor :record ":name" نهائيًا من المحذوفات.'),

    'record' => 'سجل',
    'records' => [
        'job-forms' => 'نموذج توظيف',
        'form-submissions' => 'تقديم على نموذج',
        'talents' => 'موهبة',
        'admins' => 'أدمن',
        'roles' => 'دور',
        'help-requests' => 'طلب مساعدة',
        'clients' => 'عميل',
        'countries' => 'دولة',
        'cities' => 'مدينة',
        'currencies' => 'عملة',
        'languages' => 'لغة',
        'industries' => 'مجال صناعة',
        'professional-fields' => 'مجال مهني',
        'job-titles' => 'مسمى وظيفي',
        'skills' => 'مهارة',
        'tools' => 'أداة',
        'workspaces' => 'مساحة عمل',
        'team' => 'عضو فريق',
        'workspace-roles' => 'دور في مساحة العمل',
    ],
];
