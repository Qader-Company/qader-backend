<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentMarketplace\Application\Services\TalentMarketplaceService;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Requests\ListTalentMarketplaceRequest;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentMarketplaceCardResource;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentMarketplaceDetailsResource;
use App\Modules\V1\TalentMarketplace\Presentation\Http\Resources\TalentExpertOpinionResource;
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

    /**
     * @response scenario="Expert opinion available" {"success":true,"data":{"talent_code":"QDR-9102","ratings":{"technical_skills":4,"communication_skills":5,"problem_solving":4,"soft_skills":5},"average_rating":4.5,"recommended_salary":{"min":"6500.0000","max":"8500.0000","currency":{"id":1,"code":"USD","symbol":"$","name":"US Dollar"}},"reports":{"hr":"Demonstrates strong leadership and collaboration.","technical":"Exhibits advanced technical proficiency.","psychology":"Displays adaptability and composure under pressure."},"final_feedback":"Recommended for hiring.","tips":[{"title":"Highlight measurable impact","priority":"high","description":"Add measurable outcomes to recent projects.","display_order":1}]}}
     * @response 404 scenario="Expert opinion unavailable" {"success":false,"message":"The expert opinion is not available yet."}
     */
    public function expertOpinion(
        Request $request,
        Workspace $workspace,
        Talent $talent,
        TalentMarketplaceService $service,
    ) {
        return ApiResponse::success(new TalentExpertOpinionResource(
            $service->expertOpinion($request->user(), $workspace, $talent),
        ));
    }
}
