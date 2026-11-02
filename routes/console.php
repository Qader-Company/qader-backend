<?php

use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferExpirationService;
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

Schedule::call(fn () => app(HiringOfferExpirationService::class)->synchronizeDue())
    ->name('hiring-offers:expire')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(HiringInterviewWorkflow::class)->synchronizeDue())
    ->name('hiring-interviews:synchronize')
    ->everyMinute()
    ->withoutOverlapping();
