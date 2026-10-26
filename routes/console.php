<?php

use App\Modules\V1\TalentApplications\Application\Services\InterviewStateSynchronizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(InterviewStateSynchronizer::class)->synchronizeDue())
    ->name('talent-interviews:synchronize')
    ->everyMinute()
    ->withoutOverlapping();
