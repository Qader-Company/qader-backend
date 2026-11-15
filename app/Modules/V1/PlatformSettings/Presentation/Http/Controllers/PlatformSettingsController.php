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

    public function updateHiring(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'hiring', [
            'offer_response_duration_hours' => ['sometimes', 'integer', 'min:1'],
            'post_interview_decision_duration_hours' => ['sometimes', 'integer', 'min:1'],
            'talent_offer_no_response_limit' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

    public function updateTalentEvaluation(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'talent-evaluation', [
            'interview_slot_rejection_limit' => ['sometimes', 'integer', 'min:0'],
            'confirmed_interview_reschedule_limit' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    public function updateTrash(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'trash', [
            'retention_days' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

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

    public function updateWebsite(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'website', [
            'footer_copyright_text_ar' => ['nullable', 'string', 'max:500'],
            'footer_copyright_text_en' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function updateMarketplace(Request $request, PlatformSettingsService $service): JsonResponse
    {
        return $this->update($request, $service, 'marketplace', [
            'anonymous_talent_image' => ['nullable', 'string', 'max:2048'],
        ]);
    }

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

        return ApiResponse::updated(
            $service->update($group, $values, $request->user()),
            __('settings.updated'),
        );
    }
}
