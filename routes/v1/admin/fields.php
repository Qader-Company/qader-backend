<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\AdminProfessionalFieldController;
use App\Modules\V1\ProfessionalFields\Presentation\Http\Controllers\AdminProfessionalFormFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->controller(AdminProfessionalFieldController::class)
    ->group(function (): void {
        Route::get('/', 'index')
            ->middleware(AdminPermissionEnum::PROFESSIONAL_FIELDS_VIEW->middleware());

        Route::post('/', 'store')
            ->middleware(AdminPermissionEnum::PROFESSIONAL_FIELDS_MANAGE->middleware());

        Route::patch('{record}', 'update')
            ->middleware(AdminPermissionEnum::PROFESSIONAL_FIELDS_MANAGE->middleware());
    });

Route::middleware(['auth:sanctum', 'abilities:access,admin'])
    ->get('form-fields', [AdminProfessionalFormFieldController::class, 'index'])
    ->middleware(AdminPermissionEnum::PROFESSIONAL_FIELDS_VIEW->middleware());
