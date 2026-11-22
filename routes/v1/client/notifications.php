<?php

use App\Modules\V1\Users\Presentation\Http\Controllers\ClientNotificationController;
use Illuminate\Support\Facades\Route;

Route::controller(ClientNotificationController::class)->whereNumber('workspace')->group(function (): void {
    Route::get('{workspace}/notifications', 'index');
    Route::get('{workspace}/notifications/unread-count', 'unreadCount');
    Route::get('{workspace}/notifications/types', 'types');
    Route::patch('{workspace}/notifications/read-all', 'markAllAsRead');
    Route::patch('{workspace}/notifications/{notification}/read', 'markAsRead');
});
