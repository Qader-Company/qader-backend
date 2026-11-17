<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Website\Application\Services\PublicTalentShowcaseService;
use App\Modules\V1\Website\Domain\Models\MarketingFaq;
use App\Modules\V1\Website\Domain\Models\MarketingPartner;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonial;
use App\Modules\V1\Website\Presentation\Http\Resources\PublicMarketingFaqResource;
use App\Modules\V1\Website\Presentation\Http\Resources\PublicMarketingPartnerResource;
use App\Modules\V1\Website\Presentation\Http\Resources\PublicMarketingTestimonialResource;
use App\Modules\V1\Website\Presentation\Http\Resources\PublicTalentShowcaseCardResource;
use App\Settings\PlatformSettings;
use App\Settings\WebsiteSettings;
use App\Support\Localization\LocalizedValue;
use Illuminate\Http\JsonResponse;

class PublicWebsiteController extends Controller
{
    public function settings(WebsiteSettings $website, PlatformSettings $platform): JsonResponse
    {
        $footer = LocalizedValue::resolve(
            $website->footer_copyright_text_en,
            $website->footer_copyright_text_ar,
        );

        return ApiResponse::success([
            'support' => [
                'email' => $platform->support_email,
                'phone' => $platform->support_phone,
                'whatsapp' => $platform->support_whatsapp,
                'response_business_days' => $platform->support_response_business_days,
            ],
            'legal' => [
                'privacy_policy_url' => $platform->privacy_policy_url,
                'terms_and_conditions_url' => $platform->terms_and_conditions_url,
            ],
            'footer' => [
                'copyright_text' => is_string($footer)
                    ? str_replace('{year}', (string) now()->year, $footer)
                    : null,
            ],
            'company' => [
                'founded_year' => $website->company_founded_year,
                'location' => LocalizedValue::resolve(
                    $website->company_location_en,
                    $website->company_location_ar,
                ),
            ],
            'referral' => [
                'commission_percentage' => $website->referral_commission_percentage,
            ],
        ]);
    }

    public function faqs(): JsonResponse
    {
        $faqs = MarketingFaq::query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(PublicMarketingFaqResource::collection($faqs));
    }

    public function partners(): JsonResponse
    {
        $partners = MarketingPartner::query()
            ->where('is_active', true)
            ->with('media')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(PublicMarketingPartnerResource::collection($partners));
    }

    public function testimonials(): JsonResponse
    {
        $testimonials = MarketingTestimonial::query()
            ->where('is_active', true)
            ->with(['translations', 'media'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(PublicMarketingTestimonialResource::collection($testimonials));
    }

    /**
     * @response {"success":true,"data":{"professional_fields":[{"slug":"all","name":"All Specializations","talents":[{"talent_code":"QDR-2847","image":"http://localhost:8000/images/default-talent-avatar.svg","job_title":{"name":"Full Stack Developer"},"seniority_level":{"value":"senior","label":"Senior"},"years_of_experience":7,"country":{"code":"EG","name":"Egypt"},"top_skills":[{"name":"React"},{"name":"Node.js"},{"name":"TypeScript"}],"indicative_salary":{"minimum":4500,"maximum":6000,"currency":{"code":"USD","symbol":"$","name":"US Dollar"}}}]},{"slug":"software-development-1","name":"Software Development","talents":[]}]}}
     */
    public function talentShowcase(PublicTalentShowcaseService $service): JsonResponse
    {
        $professionalFields = $service->groups()
            ->map(fn (array $group): array => [
                'slug' => $group['slug'],
                'name' => $group['name'],
                'talents' => PublicTalentShowcaseCardResource::collection($group['talents']),
            ])
            ->values();

        return ApiResponse::success([
            'professional_fields' => $professionalFields,
        ]);
    }
}
