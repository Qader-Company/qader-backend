<?php

namespace App\Modules\V1\Trash\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Trash\Application\Services\TrashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTrashDeletionController extends Controller
{
    public function __invoke(Request $request, int $record, TrashService $service): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $service->softDelete((string) $request->route('trashResource'), $record, $request->user());

        return ApiResponse::deleted();
    }
}
