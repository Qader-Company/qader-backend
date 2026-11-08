<?php

namespace Database\Seeders;

use App\Modules\V1\TalentApplications\Domain\Models\TalentReviewFaq;
use App\Modules\V1\TalentApplications\Domain\Models\TalentReviewFaqTranslation;
use Illuminate\Database\Seeder;

class TalentReviewFaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $index => $data) {
            $translation = TalentReviewFaqTranslation::query()
                ->where('locale', 'en')
                ->where('question', $data['question']['en'])
                ->first();
            $faq = $translation?->talentReviewFaq ?? new TalentReviewFaq;
            $faq->fill([
                'screen_states' => $data['screen_states'],
                'display_order' => $index,
                'is_active' => true,
            ]);

            foreach (['en', 'ar'] as $locale) {
                $localized = $faq->translateOrNew($locale);
                $localized->question = $data['question'][$locale];
                $localized->answer = $data['answer'][$locale];
            }

            $faq->save();
        }
    }

    private function faqs(): array
    {
        $reviewStates = ['under_review', 'rejected'];
        $interviewStates = [
            'interview_slot_required',
            'awaiting_new_interview_slots',
            'interview_confirmed',
            'interview_completed',
        ];

        return [
            [
                'screen_states' => $reviewStates,
                'question' => [
                    'en' => 'What does "Under Review" mean?',
                    'ar' => 'ماذا تعني حالة "قيد المراجعة"؟',
                ],
                'answer' => [
                    'en' => 'Your application has been received and is currently being reviewed by our recruitment team to assess your qualifications and experience.',
                    'ar' => 'تم استلام طلبك ويقوم فريق التوظيف حاليًا بمراجعته لتقييم مؤهلاتك وخبراتك.',
                ],
            ],
            [
                'screen_states' => [...$reviewStates, ...$interviewStates],
                'question' => [
                    'en' => 'How long does the review process take?',
                    'ar' => 'كم تستغرق عملية المراجعة؟',
                ],
                'answer' => [
                    'en' => 'The review time may vary depending on the number of applications. You will be notified as soon as your application moves to the next stage.',
                    'ar' => 'قد تختلف مدة المراجعة حسب عدد الطلبات، وسيتم إشعارك بمجرد انتقال طلبك إلى المرحلة التالية.',
                ],
            ],
            [
                'screen_states' => $reviewStates,
                'question' => [
                    'en' => 'Can I update my profile while my application is under review?',
                    'ar' => 'هل يمكنني تعديل ملفي أثناء مراجعة الطلب؟',
                ],
                'answer' => [
                    'en' => 'Your submitted application is locked during review so the reviewer sees the same information you originally submitted.',
                    'ar' => 'يظل الطلب المرسل ثابتًا أثناء المراجعة حتى يراجع الفريق نفس البيانات التي قدمتها وقت الإرسال.',
                ],
            ],
            [
                'screen_states' => $reviewStates,
                'question' => [
                    'en' => 'Does "Under Review" mean I have been shortlisted?',
                    'ar' => 'هل تعني حالة "قيد المراجعة" أنني تأهلت؟',
                ],
                'answer' => [
                    'en' => 'No. It means the review has started. You will be notified separately if you qualify for an interview.',
                    'ar' => 'لا، تعني أن المراجعة بدأت. سيتم إشعارك بشكل منفصل إذا تأهلت للمقابلة.',
                ],
            ],
            [
                'screen_states' => $interviewStates,
                'question' => [
                    'en' => 'What happens after I pass the initial review?',
                    'ar' => 'ماذا يحدث بعد اجتياز المراجعة الأولية؟',
                ],
                'answer' => [
                    'en' => 'You have passed the initial review. The next step is to choose one of the available interview time slots.',
                    'ar' => 'لقد اجتزت المراجعة الأولية. الخطوة التالية هي اختيار أحد مواعيد المقابلة المتاحة.',
                ],
            ],
        ];
    }
}
