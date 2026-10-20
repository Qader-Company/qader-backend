<?php

return [
    'application' => [
        'not_submitted' => 'لم يتم تقديم ملفك للمراجعة حتى الآن.',
    ],
    'review' => [
        'invalid_applications' => 'طلب واحد أو أكثر من الطلبات المحددة غير صالح.',
        'assigned_to_another_reviewer' => 'الطلب معين لمراجع آخر. يجب إعادة تعيينه قبل بدء التقييم.',
        'evaluation_not_editable' => 'لم يعد من الممكن تعديل هذا التقييم.',
        'invalid_interviewers' => 'يجب أن يكون كل المقابلين من حسابات الإدارة النشطة.',
        'duplicate_slots' => 'يجب أن يكون لكل موعد مقابلة وقت بداية مختلف.',
        'interviewer_time_conflict' => 'مراجع واحد أو أكثر غير متاح خلال الموعد المحدد.',
        'invalid_reviewer' => 'يجب أن يكون المراجع المحدد نشطًا ولديه صلاحية التقييم.',
        'application_closed' => 'لم يعد من الممكن مراجعة هذا الطلب أو إعادة تعيينه.',
        'current_reviewer_required' => 'المراجع المعين حاليًا هو فقط من يمكنه تنفيذ هذا الإجراء.',
        'decision_not_allowed' => 'لا يمكن تسجيل قرار للطلب في حالته الحالية.',
        'evaluation_required' => 'يجب إكمال تقييم الموهبة قبل تسجيل القرار.',
        'decision_already_recorded' => 'تم تسجيل قرار لهذا الطلب بالفعل.',
        'reviewer_assigned' => 'تم تعيين المراجع بنجاح.',
        'evaluation_saved' => 'تم حفظ تقييم الموهبة بنجاح.',
        'application_rejected' => 'تم رفض طلب الموهبة بنجاح.',
        'interview_scheduled' => 'تم حفظ تفاصيل المقابلة والمواعيد المتاحة بنجاح.',
        'cv_not_found' => 'لم يتم العثور على نسخة السيرة الذاتية المرسلة.',
    ],
    'onboarding' => [
        'previous_step_required' => 'يجب إكمال خطوة :step قبل المتابعة.',
        'steps' => [
            'personal-information' => 'المعلومات الشخصية',
            'skills-and-experiences' => 'المهارات والخبرات',
            'work-preferences' => 'تفضيلات العمل',
        ],
    ],
];
