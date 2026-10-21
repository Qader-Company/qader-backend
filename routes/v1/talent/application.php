<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\TalentPermissionEnum;
use App\Modules\V1\TalentApplications\Presentation\Http\Controllers\TalentApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware(TalentPermissionEnum::PLATFORM_ACCESS->middleware())->group(function (): void {
    Route::get('/', [TalentApplicationController::class, 'show']);
    Route::post('interview/slots/{slot}/select', [TalentApplicationController::class, 'selectInterviewSlot']);
    Route::post('interview/slots/reject', [TalentApplicationController::class, 'rejectInterviewSlots']);
    Route::post('interview/cancel', [TalentApplicationController::class, 'cancelInterview']);
});
