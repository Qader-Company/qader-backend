<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Website\Application\Services\WebsiteContentService;
use App\Modules\V1\Website\Domain\Models\MarketingFaq;
use App\Modules\V1\Website\Presentation\Http\Requests\StoreMarketingFaqRequest;
use App\Modules\V1\Website\Presentation\Http\Requests\UpdateMarketingFaqRequest;
use App\Modules\V1\Website\Presentation\Http\Resources\AdminMarketingFaqResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMarketingFaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = MarketingFaq::query()
            ->with('translations')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'faqs' => AdminMarketingFaqResource::collection($paginator->getCollection()),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function show(MarketingFaq $faq): JsonResponse
    {
        return ApiResponse::success(new AdminMarketingFaqResource($faq->load('translations')));
    }

    public function store(StoreMarketingFaqRequest $request, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::created(new AdminMarketingFaqResource($service->createFaq($request->validated())));
    }

    public function update(UpdateMarketingFaqRequest $request, MarketingFaq $faq, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminMarketingFaqResource($service->updateFaq($faq, $request->validated())));
    }

    public function destroy(MarketingFaq $faq): JsonResponse
    {
        $faq->delete();

        return ApiResponse::deleted();
    }

    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
