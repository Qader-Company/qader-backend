<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\TalentApplications\Application\Services\AdminApplicationActor;
use App\Modules\V1\TalentApplications\Application\Services\AdminTalentStateService;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\AddTalentToBlacklistRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\EditTalentReapplyRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\ListAdminTalentsRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Requests\RemoveTalentFromBlacklistRequest;
use App\Modules\V1\TalentApplications\Presentation\Http\Resources\AdminTalentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin
 *
 * @subgroup Talents
 */
class AdminTalentController extends Controller
{
    public function index(ListAdminTalentsRequest $request, AdminTalentStateService $service): JsonResponse
    {
        $result = $service->paginate($request->validated(), $request->user());
        $paginator = $result['talents'];

        return ApiResponse::success([
            'talents' => AdminTalentResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'summary' => $result['summary'],
            ],
        ]);
    }

    public function show(Request $request, int $talent, AdminTalentStateService $service): JsonResponse
    {
        return ApiResponse::success(new AdminTalentResource($service->find($talent, $request->user())));
    }

    public function addToBlacklist(
        AddTalentToBlacklistRequest $request,
        int $talent,
        AdminApplicationActor $actors,
        AdminTalentStateService $service,
    ): JsonResponse {
        return ApiResponse::updated(
            new AdminTalentResource($service->addToBlacklist(
                $talent,
                $request->string('blacklist_reason')->toString(),
                $actors->resolve($request->user()),
            )),
            __('talents.blacklist.added'),
        );
    }

    public function removeFromBlacklist(
        RemoveTalentFromBlacklistRequest $request,
        int $talent,
        AdminApplicationActor $actors,
        AdminTalentStateService $service,
    ): JsonResponse {
        return ApiResponse::updated(
            new AdminTalentResource($service->removeFromBlacklist(
                $talent,
                $request->validated('reapply_after_months'),
                $actors->resolve($request->user()),
            )),
            __('talents.blacklist.removed'),
        );
    }

    public function editReapply(
        EditTalentReapplyRequest $request,
        int $talent,
        AdminApplicationActor $actors,
        AdminTalentStateService $service,
    ): JsonResponse {
        return ApiResponse::updated(
            new AdminTalentResource($service->editReapply(
                $talent,
                $request->integer('reapply_after_months'),
                $actors->resolve($request->user()),
            )),
            __('talents.blacklist.reapply_updated'),
        );
    }
}
