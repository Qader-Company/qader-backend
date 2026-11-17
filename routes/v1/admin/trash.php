<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Trash\Presentation\Http\Controllers\AdminTrashController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminTrashController::class)
    ->group(function (): void {
        $resources = [
            'job-forms', 'form-submissions', 'talents', 'admins', 'roles', 'help-requests', 'clients',
            'countries', 'cities', 'currencies', 'languages', 'industries', 'professional-fields',
            'job-titles', 'skills', 'tools',
        ];

        foreach ($resources as $resource) {
            Route::prefix($resource)->group(function () use ($resource): void {
                Route::get('/', 'index')->defaults('trashResource', $resource)->middleware(AdminPermissionEnum::TRASH_VIEW->middleware());
                Route::post('{record}/restore', 'restore')->whereNumber('record')->defaults('trashResource', $resource)->middleware(AdminPermissionEnum::TRASH_RESTORE->middleware());
                Route::post('{record}/remove', 'remove')->whereNumber('record')->defaults('trashResource', $resource)->middleware(AdminPermissionEnum::TRASH_REMOVE->middleware());
            });
        }
    });
