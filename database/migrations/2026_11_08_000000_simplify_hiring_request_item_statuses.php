<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hiring_request_items')
            ->whereIn('status', [
                'draft',
                'awaiting_talent_response',
                'negotiating',
                'awaiting_interview_slots',
                'interview_scheduled',
                'awaiting_decisions',
                'ready_to_proceed',
                'ready_for_contract',
            ])
            ->update([
                'status' => DB::raw("CASE
                    WHEN status = 'draft' THEN 'pending'
                    WHEN status IN ('awaiting_talent_response', 'negotiating') THEN 'offer'
                    WHEN status IN ('awaiting_interview_slots', 'interview_scheduled', 'awaiting_decisions') THEN 'interview'
                    WHEN status IN ('ready_to_proceed', 'ready_for_contract') THEN 'accepted'
                    ELSE status
                END"),
            ]);

        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->string('status', 32)->default('pending')->change();
        });
    }

    public function down(): void
    {
        DB::table('hiring_request_items')
            ->whereIn('status', ['offer', 'interview', 'accepted'])
            ->update([
                'status' => DB::raw("CASE
                    WHEN status = 'offer' THEN 'awaiting_talent_response'
                    WHEN status = 'interview' THEN 'awaiting_interview_slots'
                    WHEN status = 'accepted' THEN 'ready_to_proceed'
                    ELSE status
                END"),
            ]);

        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->string('status', 32)->default('draft')->change();
        });
    }
};
