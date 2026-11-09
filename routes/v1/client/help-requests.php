<?php

use App\Modules\V1\HelpRequests\Presentation\Http\Controllers\ClientHelpRequestController;
use Illuminate\Support\Facades\Route;

Route::get('{workspace}/help-requests/options', [ClientHelpRequestController::class, 'options'])->whereNumber('workspace');
Route::post('{workspace}/help-requests', [ClientHelpRequestController::class, 'store'])->whereNumber('workspace');
