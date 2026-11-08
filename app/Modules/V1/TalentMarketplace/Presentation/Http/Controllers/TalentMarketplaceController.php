<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentMarketplaceService;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Requests\ListTalentMarketplaceRequest;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentMarketplaceCardResource;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentMarketplaceDetailsResource;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\Request;

class TalentMarketplaceController extends Controller
{
    public function index(
        ListTalentMarketplaceRequest $request,
        Workspace $workspace,
        TalentMarketplaceService $service,
    ) {
        $result = $service->list($request->user(), $workspace, $request->validated());
        $payload = TalentMarketplaceCardResource::collection($result['paginator'])
            ->response()
            ->getData(true);
        $payload['meta']['selected_talents_count'] = $result['selected_talents_count'];

        return ApiResponse::success($payload);
    }

    public function filters(Request $request, Workspace $workspace, TalentMarketplaceService $service)
    {
        return ApiResponse::success($service->filters($request->user(), $workspace));
    }

    public function show(
        Request $request,
        Workspace $workspace,
        Talent $talent,
        TalentMarketplaceService $service,
    ) {
        return ApiResponse::success(new TalentMarketplaceDetailsResource(
            $service->details($request->user(), $workspace, $talent),
        ));
    }
}
