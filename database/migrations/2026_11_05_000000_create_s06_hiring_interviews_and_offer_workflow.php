<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiring_request_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('actor_user_id')->nullable()->change();
        });

        Schema::table('talents', function (Blueprint $table): void {
            $table->unsignedInteger('offer_no_response_count')->default(0);
        });

        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->string('proposed_by', 16)->default('client')->after('request_item_id');
            $table->unsignedInteger('notice_period_days')->nullable()->after('work_hours');
            $table->string('work_location', 500)->nullable()->after('notice_period_days');
        });

        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->string('client_decision', 16)->nullable()->after('cancellation_reason');
            $table->text('client_decision_reason')->nullable()->after('client_decision');
            $table->timestamp('client_decided_at')->nullable()->after('client_decision_reason');
            $table->timestamp('decision_due_at')->nullable()->after('client_decided_at');
        });

        Schema::create('hiring_interviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_item_id')->unique()->constrained('hiring_request_items')->cascadeOnDelete();
            $table->string('status', 32)->default('awaiting_responses');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->foreignId('proposed_by_admin_id')->constrained('admins')->restrictOnDelete();
            $table->timestamp('proposed_at');
            $table->string('client_response', 32)->default('pending');
            $table->text('client_response_reason')->nullable();
            $table->timestamp('client_responded_at')->nullable();
            $table->string('talent_response', 32)->default('pending');
            $table->text('talent_response_reason')->nullable();
            $table->timestamp('talent_responded_at')->nullable();
            $table->foreignId('confirmed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('google_calendar_event_id', 255)->nullable();
            $table->string('meeting_url', 2048)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['proposed_by_admin_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_interviews');

        Schema::table('hiring_request_items', function (Blueprint $table): void {
            $table->dropColumn([
                'client_decision', 'client_decision_reason', 'client_decided_at',
                'talent_decision', 'talent_decision_reason', 'talent_decided_at',
                'decision_due_at',
            ]);
        });

        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->dropColumn(['proposed_by', 'notice_period_days', 'work_location']);
        });

        Schema::table('talents', function (Blueprint $table): void {
            $table->dropColumn('offer_no_response_count');
        });

        Schema::table('hiring_request_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('actor_user_id')->nullable(false)->change();
        });
    }
};
