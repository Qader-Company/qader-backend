<?php

use App\Http\Controllers\PublicStorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback for shared hosts that cannot serve Laravel's public storage symlink.
// Apache/Nginx serves existing files directly; Laravel handles only unresolved paths.
Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('public-storage.show');
