<?php

namespace App\Modules\V1\Website\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Website\Application\Services\WebsiteContentService;
use App\Modules\V1\Website\Domain\Models\MarketingPartner;
use App\Modules\V1\Website\Presentation\Http\Requests\StoreMarketingPartnerRequest;
use App\Modules\V1\Website\Presentation\Http\Requests\UpdateMarketingPartnerRequest;
use App\Modules\V1\Website\Presentation\Http\Resources\AdminMarketingPartnerResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMarketingPartnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = MarketingPartner::query()
            ->with('media')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'partners' => AdminMarketingPartnerResource::collection($paginator->getCollection()),
            'meta' => $this->meta($paginator),
        ]);
    }

    public function show(MarketingPartner $partner): JsonResponse
    {
        return ApiResponse::success(new AdminMarketingPartnerResource($partner->load('media')));
    }

    public function store(StoreMarketingPartnerRequest $request, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::created(new AdminMarketingPartnerResource($service->createPartner($request->validated())));
    }

    public function update(UpdateMarketingPartnerRequest $request, MarketingPartner $partner, WebsiteContentService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminMarketingPartnerResource($service->updatePartner($partner, $request->validated())));
    }

    public function destroy(MarketingPartner $partner): JsonResponse
    {
        $partner->delete();

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
