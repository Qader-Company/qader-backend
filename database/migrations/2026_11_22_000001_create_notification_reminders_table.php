<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reminders', function (Blueprint $table): void {
            $table->id();
            // e.g. hiring_interview:12:before:1763800000 — includes the scheduled time, so a reschedule gets new reminders.
            $table->string('key')->unique();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reminders');
    }
};
