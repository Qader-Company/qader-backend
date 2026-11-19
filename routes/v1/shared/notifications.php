<?php

use App\Modules\V1\Users\Presentation\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NotificationController::class, 'index']);
Route::get('unread-count', [NotificationController::class, 'unreadCount']);
Route::patch('read-all', [NotificationController::class, 'markAllAsRead']);
Route::patch('{notification}/read', [NotificationController::class, 'markAsRead']);
