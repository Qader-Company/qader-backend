<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Website\Application\Services\WebsiteContentService;
use App\Modules\V1\Website\Domain\Models\MarketingTestimonial;
use App\Modules\V1\Website\Presentation\Http\Requests\StoreMarketingTestimonialRequest;
use App\Modules\V1\Website\Presentation\Http\Requests\UpdateMarketingTestimonialRequest;
use App\Modules\V1\Website\Presentation\Http\Resources\AdminMarketingTestimonialResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMarketingTestimonialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = MarketingTestimonial::query()
            ->with(['translations', 'media'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'testimonials' => AdminMarketingTestimonialResource::collection($paginator->getCollection()),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function show(MarketingTestimonial $testimonial): JsonResponse
    {
        return ApiResponse::success(new AdminMarketingTestimonialResource($testimonial->load(['translations', 'media'])));
    }

    public function store(StoreMarketingTestimonialRequest $request, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::created(new AdminMarketingTestimonialResource($service->createTestimonial($request->validated())));
    }

    public function update(UpdateMarketingTestimonialRequest $request, MarketingTestimonial $testimonial, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminMarketingTestimonialResource($service->updateTestimonial($testimonial, $request->validated())));
    }

    public function destroy(MarketingTestimonial $testimonial): JsonResponse
    {
        $testimonial->delete();

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
