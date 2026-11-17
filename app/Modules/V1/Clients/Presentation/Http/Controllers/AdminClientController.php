<?php

namespace App\Modules\V1\Clients\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Clients\Application\Services\AdminClientService;
use App\Modules\V1\Clients\Application\Services\AdminClientWorkspaceService;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientAccountStatusEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientListSortEnum;
use App\Modules\V1\Clients\Domain\ValueObjects\ClientRegistrationPeriodEnum;
use App\Modules\V1\Clients\Presentation\Http\Requests\DeleteClientRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\DeleteClientWorkspaceRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\ListAdminClientsRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\ListClientWorkspacesRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\ListWorkspaceTeamRequest;
use App\Modules\V1\Clients\Presentation\Http\Requests\SuspendClientRequest;
use App\Modules\V1\Clients\Presentation\Http\Resources\AdminClientResource;
use App\Modules\V1\Clients\Presentation\Http\Resources\AdminClientWorkspaceResource;
use App\Modules\V1\Clients\Presentation\Http\Resources\AdminWorkspaceMemberResource;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceStatusEnum;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group Admin
 *
 * @subgroup Clients
 */
class AdminClientController extends Controller
{
    public function options(): JsonResponse
    {
        return ApiResponse::success([
            'statuses' => EnumOptions::make(ClientAccountStatusEnum::class, 'client_account_statuses'),
            'registration_periods' => EnumOptions::make(ClientRegistrationPeriodEnum::class, 'client_registration_periods'),
            'sorts' => EnumOptions::make(ClientListSortEnum::class, 'client_list_sorts'),
            'workspace_statuses' => EnumOptions::make(WorkspaceStatusEnum::class, 'workspace_statuses'),
            'workspace_sorts' => EnumOptions::make(ClientListSortEnum::class, 'client_list_sorts'),
            'member_statuses' => EnumOptions::make(WorkspaceMemberStatusEnum::class, 'workspace_member_statuses'),
        ]);
    }

    public function index(ListAdminClientsRequest $request, AdminClientService $service): JsonResponse
    {
        $paginator = $service->paginate($request->validated(), $request->user());

        return ApiResponse::success(['clients' => AdminClientResource::collection($paginator->getCollection()), 'meta' => $this->meta($paginator)]);
    }

    public function show(Request $request, int $client, AdminClientService $service): JsonResponse
    {
        return ApiResponse::success(new AdminClientResource($service->show($client, $request->user())));
    }

    public function suspend(SuspendClientRequest $request, int $client, AdminClientService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminClientResource($service->suspend($client, $request->validated('reason'), $request->user())), __('clients.suspended'));
    }

    public function reactivate(Request $request, int $client, AdminClientService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminClientResource($service->reactivate($client, $request->user())), __('clients.reactivated'));
    }

    public function destroy(DeleteClientRequest $request, int $client, AdminClientService $service): JsonResponse
    {
        $service->delete($client, $request->validated('reason'), $request->user());

        return ApiResponse::deleted(__('clients.deleted'));
    }

    public function workspaces(ListClientWorkspacesRequest $request, int $client, AdminClientWorkspaceService $service): JsonResponse
    {
        $paginator = $service->paginate($client, $request->validated(), $request->user());

        return ApiResponse::success(['workspaces' => AdminClientWorkspaceResource::collection($paginator->getCollection()), 'meta' => $this->meta($paginator)]);
    }

    public function workspace(Request $request, int $client, int $workspace, AdminClientWorkspaceService $service): JsonResponse
    {
        return ApiResponse::success(new AdminClientWorkspaceResource($service->show($client, $workspace, $request->user())));
    }

    public function activateWorkspace(Request $request, int $client, int $workspace, AdminClientWorkspaceService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminClientWorkspaceResource($service->activate($client, $workspace, $request->user())), __('clients.workspace_activated'));
    }

    public function deactivateWorkspace(Request $request, int $client, int $workspace, AdminClientWorkspaceService $service): JsonResponse
    {
        return ApiResponse::updated(new AdminClientWorkspaceResource($service->deactivate($client, $workspace, $request->user())), __('clients.workspace_deactivated'));
    }

    public function destroyWorkspace(DeleteClientWorkspaceRequest $request, int $client, int $workspace, AdminClientWorkspaceService $service): JsonResponse
    {
        $service->delete($client, $workspace, $request->validated('reason'), $request->user());

        return ApiResponse::deleted(__('clients.workspace_deleted'));
    }

    public function members(ListWorkspaceTeamRequest $request, int $client, int $workspace, AdminClientWorkspaceService $service): JsonResponse
    {
        $paginator = $service->members($client, $workspace, $request->validated(), $request->user());
        $locale = app()->getLocale();

        return ApiResponse::success([
            'members' => AdminWorkspaceMemberResource::collection($paginator->getCollection()),
            'meta' => $this->meta($paginator) + [
                // Options for the Figma "All Squads" filter: the workspace roles.
                'roles' => WorkspaceRole::query()->where('workspace_id', $workspace)->orderBy('id')->get()
                    ->map(fn (WorkspaceRole $role): array => [
                        'id' => $role->id,
                        'key' => $role->key,
                        'name' => ($locale === 'ar' ? $role->name_ar : $role->name_en) ?: $role->name_en,
                    ])->values(),
            ],
        ]);
    }

    public function destroyMember(Request $request, int $client, int $workspace, int $member, AdminClientWorkspaceService $service): JsonResponse
    {
        $service->deleteMember($client, $workspace, $member, $request->user());

        return ApiResponse::deleted(__('clients.member_deleted'));
    }

    /** View the tax card inline, or download it with ?download=1. */
    public function taxCard(Request $request, int $client, int $workspace, AdminClientWorkspaceService $service): BinaryFileResponse
    {
        $media = $service->taxCard($client, $workspace);

        return $request->boolean('download')
            ? response()->download($media->getPath(), $media->file_name)
            : response()->file($media->getPath(), ['Content-Type' => $media->mime_type]);
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
