<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Website\Presentation\Http\Controllers\AdminMarketingFaqController;
use App\Modules\V1\Website\Presentation\Http\Controllers\AdminMarketingPartnerController;
use App\Modules\V1\Website\Presentation\Http\Controllers\AdminMarketingTestimonialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:access,admin'])->group(function (): void {
    Route::apiResource('faqs', AdminMarketingFaqController::class)
        ->middlewareFor(['index', 'show'], AdminPermissionEnum::WEBSITE_FAQS_VIEW->middleware())
        ->middlewareFor(['store', 'update', 'destroy'], AdminPermissionEnum::WEBSITE_FAQS_MANAGE->middleware());

    Route::apiResource('partners', AdminMarketingPartnerController::class)
        ->middlewareFor(['index', 'show'], AdminPermissionEnum::WEBSITE_PARTNERS_VIEW->middleware())
        ->middlewareFor(['store', 'update', 'destroy'], AdminPermissionEnum::WEBSITE_PARTNERS_MANAGE->middleware());

    Route::apiResource('testimonials', AdminMarketingTestimonialController::class)
        ->middlewareFor(['index', 'show'], AdminPermissionEnum::WEBSITE_TESTIMONIALS_VIEW->middleware())
        ->middlewareFor(['store', 'update', 'destroy'], AdminPermissionEnum::WEBSITE_TESTIMONIALS_MANAGE->middleware());
});
