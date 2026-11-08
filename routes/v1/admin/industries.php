<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Industries\Presentation\Http\Controllers\AdminIndustryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->controller(AdminIndustryController::class)->group(function () {
    Route::get('/', 'index')->middleware(AdminPermissionEnum::INDUSTRIES_VIEW->middleware());
    Route::post('/', 'store')->middleware(AdminPermissionEnum::INDUSTRIES_MANAGE->middleware());
    Route::patch('{industry}', 'update')->middleware(AdminPermissionEnum::INDUSTRIES_MANAGE->middleware());
});
