<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\TalentPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentApplicationController;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentApplicationEnumController;
use Illuminate\Support\Facades\Route;

// Read-only: available before the first submission, so a talent without an application gets a clear 404
// ("No application has been submitted yet.") instead of a 403 from the platform-access permission.
Route::get('options', [TalentApplicationEnumController::class, 'options']);
Route::get('/', [TalentApplicationController::class, 'show']);

Route::middleware(TalentPermissionEnum::PLATFORM_ACCESS->middleware())->group(function (): void {
    Route::post('interview/slots/{slot}/select', [TalentApplicationController::class, 'selectInterviewSlot']);
    Route::post('interview/slots/reject', [TalentApplicationController::class, 'rejectInterviewSlots']);
    Route::post('interview/cancel', [TalentApplicationController::class, 'cancelInterview']);
});
