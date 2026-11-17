<?php

use App\Modules\V1\Website\Presentation\Http\Controllers\PublicWebsiteController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicWebsiteController::class)->group(function (): void {
    Route::get('/', 'settings');
    Route::get('faqs', 'faqs');
    Route::get('partners', 'partners');
    Route::get('testimonials', 'testimonials');
});
