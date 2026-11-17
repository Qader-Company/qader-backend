<?php

namespace Database\Seeders;

use App\Modules\V1\Website\Domain\Models\MarketingFaq;
use App\Modules\V1\Website\Domain\Models\MarketingFaqTranslation;
use App\Modules\V1\Website\Domain\Models\MarketingPartner;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonial;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonialTranslation;
use Illuminate\Database\Seeder;

class WebsiteMarketingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqs();
        $this->seedPartners();
        $this->seedTestimonials();
    }

    private function seedFaqs(): void
    {
        foreach ($this->faqs() as $index => $data) {
            $translation = MarketingFaqTranslation::query()
                ->where('locale', 'ar')
                ->where('question', $data['ar']['question'])
                ->first();
            $faq = $translation?->marketingFaq()->withTrashed()->first() ?? new MarketingFaq;
            $faq->fill([
                'display_order' => $index + 1,
                'is_active' => true,
            ]);
            $faq->deleted_at = null;

            foreach (['ar', 'en'] as $locale) {
                $translation = $faq->translateOrNew($locale);
                $translation->question = $data[$locale]['question'];
                $translation->answer = $data[$locale]['answer'];
            }

            $faq->save();
        }
    }

    private function seedPartners(): void
    {
        foreach (range(1, 5) as $order) {
            $partner = MarketingPartner::withTrashed()->firstOrNew(['display_order' => $order]);
            $partner->fill([
                'name' => match ($order) {
                    1, 4 => 'Tuba',
                    3 => 'First Step',
                    default => null,
                },
                'url' => null,
                'display_order' => $order,
                'is_active' => true,
            ]);
            $partner->deleted_at = null;
            $partner->save();

            if (! $partner->hasMedia('logo')) {
                $asset = database_path("seeders/assets/website/partners/partner-{$order}.png");
                $partner->addMedia($asset)
                    ->preservingOriginal()
                    ->toMediaCollection('logo', 'public');
            }
        }
    }

    private function seedTestimonials(): void
    {
        foreach ($this->testimonials() as $index => $data) {
            $translation = MarketingTestimonialTranslation::query()
                ->where('locale', 'en')
                ->where('name', $data['en']['name'])
                ->first();
            $testimonial = $translation?->marketingTestimonial()->withTrashed()->first()
                ?? new MarketingTestimonial;
            $testimonial->fill([
                'display_order' => $index + 1,
                'is_active' => true,
            ]);
            $testimonial->deleted_at = null;

            foreach (['ar', 'en'] as $locale) {
                $localized = $testimonial->translateOrNew($locale);
                $localized->name = $data[$locale]['name'];
                $localized->role = $data[$locale]['role'];
                $localized->quote = $data[$locale]['quote'];
            }

            $testimonial->save();
        }
    }

    /** @return array<int, array{ar: array{question: string, answer: string}, en: array{question: string, answer: string}}> */
    private function faqs(): array
    {
        return [
            [
                'ar' => [
                    'question' => 'ما الفرق بين خدمات قادر الثلاث؟',
                    'answer' => "في استقطاب المواهب: نساعدك في البحث والتقييم بينما تتولى شركتك التوظيف.\nوفي الفرق المخصصة: نبني لك فريقًا في مصر مع بيئة عمل مجهزة ودعم تشغيلي.\nأما المشروعات الرقمية: فنتولى تنفيذ وتسليم مشروع كامل وفق نطاق متفق عليه.",
                ],
                'en' => [
                    'question' => "What's the difference between Qader's three services?",
                    'answer' => "Talent Acquisition: we source and assess candidates while your company does the hiring.\nDedicated Teams: we build a team for you in Egypt with an equipped workspace and operational support.\nDigital Projects: we deliver a complete project within an agreed scope.",
                ],
            ],
            [
                'ar' => [
                    'question' => 'هل توفر قادر كفاءات تقنية وغير تقنية؟',
                    'answer' => 'نعم. نوفر كفاءات في التقنية وتطوير البرمجيات، والتسويق الرقمي والمحتوى، والمبيعات وخدمة العملاء، والأدوار التشغيلية والإدارية والمالية.',
                ],
                'en' => [
                    'question' => 'Does Qader provide both technical and non-technical talent?',
                    'answer' => 'Yes. We cover software and technology, digital marketing and content, sales and customer service, and operations, administration and finance roles.',
                ],
            ],
            [
                'ar' => [
                    'question' => 'هل تشمل الفرق المخصصة مقرات عمل مجهزة؟',
                    'answer' => 'نعم. توفر قادر مقرات عمل مجهزة في مصر، إلى جانب البنية اللازمة للعمل وإدارة شؤون الموظفين والرواتب والدعم التشغيلي.',
                ],
                'en' => [
                    'question' => 'Do dedicated teams include an equipped workspace?',
                    'answer' => 'Yes. Qader provides equipped offices in Egypt, plus the infrastructure, HR administration, payroll and operational support the team needs.',
                ],
            ],
            [
                'ar' => [
                    'question' => 'من المسؤول عن إدارة الفريق والمهام اليومية؟',
                    'answer' => 'تحدد شركتك أهداف الفريق وأولوياته وتتابع المهام اليومية، بينما تتولى قادر الجوانب التشغيلية والإدارية. ويمكن الاتفاق على دعم إضافي في الإشراف وفق نموذج التعاون.',
                ],
                'en' => [
                    'question' => 'Who manages the team and day-to-day tasks?',
                    'answer' => "Your company sets the team's goals and priorities and follows daily work, while Qader handles operations and administration. Extra supervision support can be agreed as part of the engagement model.",
                ],
            ],
            [
                'ar' => [
                    'question' => 'كيف تحدد تكلفة الخدمة وموعد البدء؟',
                    'answer' => 'نحدد التكلفة وموعد البدء بعد فهم احتياجك: عدد الأدوار ومستوى الخبرة ونطاق العمل والخدمة المختارة. شاركنا احتياجك عبر النموذج وسنعود إليك بعرض واضح.',
                ],
                'en' => [
                    'question' => 'How are cost and start date decided?',
                    'answer' => "We set cost and start date once we understand your need: number of roles, seniority, scope and the chosen service. Share your need through the form and we'll come back with a clear proposal.",
                ],
            ],
        ];
    }

    /** @return array<int, array{ar: array{name: string, role: string, quote: string}, en: array{name: string, role: string, quote: string}}> */
    private function testimonials(): array
    {
        return [
            [
                'ar' => [
                    'name' => 'تميم محمد',
                    'role' => 'مطوّر Back End مبتدئ',
                    'quote' => 'سعيد جدًا بكوني جزءًا من قادر. الفريق داعم، والتواصل دائمًا سلس، وأشعر فعلًا بالتقدير هنا.',
                ],
                'en' => [
                    'name' => 'Tamem Mohamed',
                    'role' => 'Jr. Back End Developer',
                    'quote' => "I'm happy to be part of Qader. The team is supportive, communication is always smooth, and I really feel appreciated here.",
                ],
            ],
            [
                'ar' => [
                    'name' => 'سارة أحمد',
                    'role' => 'مصممة UI/UX',
                    'quote' => 'منذ اليوم الأول شعرت بأن صوتي مسموع. أعمل على مشروعات حقيقية لعملاء في الخليج، وكل مشروع يضيف إليّ خبرة جديدة.',
                ],
                'en' => [
                    'name' => 'Sara Ahmed',
                    'role' => 'UI/UX Designer',
                    'quote' => 'From day one I felt heard. I work on real projects for Gulf clients, and each one teaches me something new.',
                ],
            ],
            [
                'ar' => [
                    'name' => 'عمر خالد',
                    'role' => 'محاسب أول',
                    'quote' => 'وفّرت لي قادر فرصة لدى شركة كبرى دون رحلة بحث طويلة، والمتابعة بعد التعيين جعلتني مطمئنًا في كل خطوة.',
                ],
                'en' => [
                    'name' => 'Omar Khaled',
                    'role' => 'Senior Accountant',
                    'quote' => 'Qader matched me with a major company without a long job search, and their follow-up made me feel confident.',
                ],
            ],
            [
                'ar' => [
                    'name' => 'مريم حسن',
                    'role' => 'أخصائية تسويق رقمي',
                    'quote' => 'بيئة العمل مرنة ومحفّزة، والتدريب المستمر ساعدني على تطوير مهاراتي بسرعة وتحمّل مسؤوليات أكبر.',
                ],
                'en' => [
                    'name' => 'Mariam Hassan',
                    'role' => 'Digital Marketing Specialist',
                    'quote' => 'The environment is flexible and motivating, and ongoing training helped me grow fast and take on more responsibility.',
                ],
            ],
        ];
    }
}
