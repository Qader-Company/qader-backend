<?php

use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferExpirationService;
use App\Modules\V1\Notifications\Application\Services\NotificationReminderService;
use App\Modules\V1\TalentApplications\Application\Services\InterviewStateSynchronizer;
use App\Modules\V1\Trash\Application\Services\TrashService;
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

Schedule::call(fn () => app(NotificationReminderService::class)->sendDue())
    ->name('notifications:reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(TrashService::class)->removeExpired())
    ->name('trash:remove-expired')
    ->dailyAt('02:00')
    ->withoutOverlapping();
