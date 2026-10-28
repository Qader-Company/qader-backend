<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentMarketplaceService;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Requests\ListWorkspaceTalentSelectionsRequest;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Requests\StoreWorkspaceTalentSelectionRequest;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentMarketplaceCardResource;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceTalentSelectionController extends Controller
{
    public function index(
        ListWorkspaceTalentSelectionsRequest $request,
        Workspace $workspace,
        TalentMarketplaceService $service,
    ) {
        $result = $service->selections(
            $request->user(),
            $workspace,
            $request->integer('per_page', 12),
        );

        return ApiResponse::success(
            TalentMarketplaceCardResource::collection($result['paginator'])
                ->response()
                ->getData(true),
        );
    }

    public function store(
        StoreWorkspaceTalentSelectionRequest $request,
        Workspace $workspace,
        TalentMarketplaceService $service,
    ) {
        return ApiResponse::created(
            new TalentMarketplaceCardResource($service->addSelection(
                $request->user(),
                $workspace,
                $request->integer('talent_id'),
            )),
            __('marketplace.selection_added'),
        );
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        Talent $talent,
        TalentMarketplaceService $service,
    ) {
        $service->removeSelection($request->user(), $workspace, $talent);

        return ApiResponse::deleted(__('marketplace.selection_removed'));
    }
}
