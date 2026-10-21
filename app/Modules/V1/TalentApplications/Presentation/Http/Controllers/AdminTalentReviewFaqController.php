<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\TalentReviewFaqService;
use App\Modules\V1\TalentApplications\Domain\Models\TalentReviewFaq;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\StoreTalentReviewFaqRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\UpdateTalentReviewFaqRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\TalentReviewFaqResource;
use Illuminate\Http\JsonResponse;

class AdminTalentReviewFaqController extends Controller
{
    /**
     * @response {"success":true,"data":[{"id":1,"screen_states":["under_review","rejected"],"question":{"en":"What does Under Review mean?","ar":"ماذا تعني حالة قيد المراجعة؟"},"answer":{"en":"Your application is currently being reviewed.","ar":"طلبك قيد المراجعة حاليًا."},"display_order":0,"is_active":true}]}
     */
    public function index(TalentReviewFaqService $faqs): JsonResponse
    {
        return ApiResponse::success(TalentReviewFaqResource::collection($faqs->all()));
    }

    /**
     * @response {"success":true,"data":{"id":1,"screen_states":["under_review","rejected"],"question":{"en":"What does Under Review mean?","ar":"ماذا تعني حالة قيد المراجعة؟"},"answer":{"en":"Your application is currently being reviewed.","ar":"طلبك قيد المراجعة حاليًا."},"display_order":0,"is_active":true}}
     */
    public function show(TalentReviewFaq $faq): JsonResponse
    {
        return ApiResponse::success(new TalentReviewFaqResource($faq->load('translations')));
    }

    /**
     * @response 201 {"success":true,"message":"Created successfully.","data":{"id":1,"screen_states":["under_review","rejected"],"question":{"en":"What does Under Review mean?","ar":"ماذا تعني حالة قيد المراجعة؟"},"answer":{"en":"Your application is currently being reviewed.","ar":"طلبك قيد المراجعة حاليًا."},"display_order":0,"is_active":true}}
     */
    public function store(StoreTalentReviewFaqRequest $request, TalentReviewFaqService $faqs): JsonResponse
    {
        return ApiResponse::created(
            new TalentReviewFaqResource($faqs->create($request->validated())),
        );
    }

    /**
     * @response {"success":true,"message":"Updated successfully.","data":{"id":1,"screen_states":["under_review","rejected"],"question":{"en":"What does Under Review mean?","ar":"ماذا تعني حالة قيد المراجعة؟"},"answer":{"en":"Your application is currently being reviewed.","ar":"طلبك قيد المراجعة حاليًا."},"display_order":0,"is_active":true}}
     */
    public function update(
        UpdateTalentReviewFaqRequest $request,
        TalentReviewFaq $faq,
        TalentReviewFaqService $faqs,
    ): JsonResponse {
        return ApiResponse::updated(
            new TalentReviewFaqResource($faqs->update($faq, $request->validated())),
        );
    }

    /**
     * @response {"success":true,"message":"Deleted successfully."}
     */
    public function destroy(TalentReviewFaq $faq): JsonResponse
    {
        $faq->delete();

        return ApiResponse::deleted();
    }
}
