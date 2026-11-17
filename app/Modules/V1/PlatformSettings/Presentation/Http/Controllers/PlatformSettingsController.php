<?php

namespace App\Modules\V1\PlatformSettings\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\PlatformSettings\Application\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformSettingsController extends Controller
{
    public function index(PlatformSettingsService $service): JsonResponse
    {
        return ApiResponse::success($service->all());
    }

    /**
     * @bodyParam offer_response_duration_hours integer Offer response deadline in hours. Example: 48
     * @bodyParam post_interview_decision_duration_hours integer Post-interview decision deadline in hours. Example: 24
     * @bodyParam talent_offer_no_response_limit integer Number of expired offers allowed before the talent account is deactivated. Example: 2
     */
    public function updateHiring(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'hiring', [
            'offer_response_duration_hours' => ['sometimes', 'integer', 'min:1'],
            'post_interview_decision_duration_hours' => ['sometimes', 'integer', 'min:1'],
            'talent_offer_no_response_limit' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

    /**
     * @bodyParam interview_slot_rejection_limit integer Number of times a talent may reject all proposed interview slots. Example: 2
     * @bodyParam confirmed_interview_reschedule_limit integer Number of confirmed interview reschedules allowed for a talent. Example: 1
     */
    public function updateTalentEvaluation(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'talent-evaluation', [
            'interview_slot_rejection_limit' => ['sometimes', 'integer', 'min:0'],
            'confirmed_interview_reschedule_limit' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    /**
     * @bodyParam retention_days integer Number of days an item remains visible in Trash. Example: 30
     */
    public function updateTrash(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'trash', [
            'retention_days' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

    /**
     * @bodyParam support_response_business_days integer Expected support response time in business days. Example: 1
     * @bodyParam support_email string Public support email; may be null. Example: info@qadereg.com
     * @bodyParam support_phone string Public support phone number; may be null. Example: +201001234567
     * @bodyParam support_whatsapp string Public WhatsApp number; may be null. Example: +201001234567
     * @bodyParam privacy_policy_url string Public privacy policy URL; may be null. Example: https://qadereg.com/privacy-policy
     * @bodyParam terms_and_conditions_url string Public terms and conditions URL; may be null. Example: https://qadereg.com/terms-and-conditions
     */
    public function updatePlatform(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'platform', [
            'support_response_business_days' => ['sometimes', 'integer', 'min:1'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:32'],
            'support_whatsapp' => ['nullable', 'string', 'max:32'],
            'privacy_policy_url' => ['nullable', 'url', 'max:2048'],
            'terms_and_conditions_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    /**
     * @bodyParam footer_copyright_text_ar string Arabic footer copyright text; may be null and `{year}` is replaced dynamically. Example: © {year} قادر. جميع الحقوق محفوظة.
     * @bodyParam footer_copyright_text_en string English footer copyright text; may be null and `{year}` is replaced dynamically. Example: © {year} Qader. All rights reserved.
     * @bodyParam company_founded_year integer Company founding year. Example: 2022
     * @bodyParam referral_commission_percentage number Referral commission percentage. Example: 10
     * @bodyParam company_location_ar string Arabic company location; may be null. Example: 6 أكتوبر، مصر
     * @bodyParam company_location_en string English company location; may be null. Example: 6th of October, Egypt
     */
    public function updateWebsite(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'website', [
            'footer_copyright_text_ar' => ['nullable', 'string', 'max:500'],
            'footer_copyright_text_en' => ['nullable', 'string', 'max:500'],
            'company_founded_year' => ['sometimes', 'integer', 'min:1900', 'max:'.now()->year],
            'referral_commission_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'company_location_ar' => ['nullable', 'string', 'max:255'],
            'company_location_en' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @bodyParam anonymous_talent_image string Legacy anonymous talent image URL; may be null. Example: http://localhost:8000/images/default-talent-avatar.svg
     */
    public function updateMarketplace(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'marketplace', [
            'anonymous_talent_image' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    /**
     * @bodyParam qader_expert_opinion_price number Qader Expert Opinion price. Example: 250
     * @bodyParam qader_expert_opinion_currency string Active three-letter currency code. Example: EGP
     * @bodyParam detailed_rejection_feedback_price number Detailed Rejection Feedback price. Example: 100
     * @bodyParam detailed_rejection_feedback_currency string Active three-letter currency code. Example: EGP
     * @bodyParam profile_expert_review_price number Profile Expert Review price. Example: 10
     * @bodyParam profile_expert_review_currency string Active three-letter currency code. Example: USD
     */
    public function updatePaidServices(Request $request, PlatformSettingsService $service): JsonResponse
    {
        $currency = Rule::exists('currencies', 'code')->where('is_active', true)->whereNull('deleted_at');

        return $this->update($request, $service, 'paid-services', [
            'qader_expert_opinion_price' => ['sometimes', 'numeric', 'gt:0'],
            'qader_expert_opinion_currency' => ['sometimes', 'string', 'size:3', $currency],
            'detailed_rejection_feedback_price' => ['sometimes', 'numeric', 'gt:0'],
            'detailed_rejection_feedback_currency' => ['sometimes', 'string', 'size:3', $currency],
            'profile_expert_review_price' => ['sometimes', 'numeric', 'gt:0'],
            'profile_expert_review_currency' => ['sometimes', 'string', 'size:3', $currency],
        ]);
    }

    private function update(Request $request, PlatformSettingsService $service, string $group, array $rules): JsonResponse
    {
        $values = $request->validate($rules);
        if ($values === []) {
            throw ValidationException::withMessages([
                'settings' => [__('settings.at_least_one_value')],
            ]);
        }

        $updated = $service->update($group, $values, $request->user());

        return ApiResponse::updated($service->inputs($group, $updated), __('settings.updated'));
    }
}
