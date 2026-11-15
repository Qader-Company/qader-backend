<?php

namespace App\Modules\V1\Trash\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Trash\Application\Services\TrashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTrashController extends Controller
{
    public function index(Request $request, TrashService $service): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ApiResponse::success($service->paginate(
            $this->resource($request),
            $filters,
            availableActions: $this->availableActions($request),
        ));
    }

    public function restore(Request $request, int $record, TrashService $service): JsonResponse
    {
        $service->restore($this->resource($request), $record);

        return ApiResponse::updated(null, __('trash.restored'));
    }

    public function remove(Request $request, int $record, TrashService $service): JsonResponse
    {
        $service->remove($this->resource($request), $record, $request->user());

        return ApiResponse::updated(null, __('trash.removed'));
    }

    private function resource(Request $request): string
    {
        return (string) $request->route('trashResource');
    }

    private function availableActions(Request $request): array
    {
        return array_values(array_filter([
            $request->user()->can(AdminPermissionEnum::TRASH_RESTORE->value) ? 'restore' : null,
            $request->user()->can(AdminPermissionEnum::TRASH_REMOVE->value) ? 'remove_from_trash' : null,
        ]));
    }
}
