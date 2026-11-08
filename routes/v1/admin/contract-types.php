<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\ContractTypes\Presentation\Http\Controllers\AdminContractTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->controller(AdminContractTypeController::class)->group(function () {
    Route::get('/', 'index')->middleware(AdminPermissionEnum::CONTRACT_TYPES_VIEW->middleware());
    Route::get('{contractType}', 'show')->whereNumber('contractType')->middleware(AdminPermissionEnum::CONTRACT_TYPES_VIEW->middleware());
    Route::put('{contractType}', 'update')->whereNumber('contractType')->middleware(AdminPermissionEnum::CONTRACT_TYPES_UPDATE->middleware());
    Route::patch('{contractType}/status', 'status')->whereNumber('contractType')->middleware(AdminPermissionEnum::CONTRACT_TYPES_TOGGLE_STATUS->middleware());
});
