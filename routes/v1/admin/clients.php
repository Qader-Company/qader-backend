<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Clients\Presentation\Http\Controllers\AdminClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminClientController::class)
    ->group(function (): void {
        $view = AdminPermissionEnum::CLIENTS_VIEW->middleware();
        $manage = AdminPermissionEnum::CLIENTS_MANAGE->middleware();
        $delete = AdminPermissionEnum::CLIENTS_DELETE->middleware();

        Route::get('options', 'options')->middleware($view);
        Route::get('/', 'index')->middleware($view);

        Route::prefix('{client}')->whereNumber('client')->group(function () use ($view, $manage, $delete): void {
            Route::get('/', 'show')->middleware($view);
            Route::post('suspend', 'suspend')->middleware($manage);
            Route::post('reactivate', 'reactivate')->middleware($manage);
            Route::delete('/', 'destroy')->middleware($delete);

            Route::get('workspaces', 'workspaces')->middleware($view);
            Route::prefix('workspaces/{workspace}')->whereNumber('workspace')->group(function () use ($view, $manage, $delete): void {
                Route::get('/', 'workspace')->middleware($view);
                Route::post('activate', 'activateWorkspace')->middleware($manage);
                Route::post('deactivate', 'deactivateWorkspace')->middleware($manage);
                Route::delete('/', 'destroyWorkspace')->middleware($delete);
                Route::get('tax-card', 'taxCard')->middleware($view);
                Route::get('members', 'members')->middleware($view);
                Route::delete('members/{member}', 'destroyMember')->whereNumber('member')->middleware($delete);
            });
        });
    });
