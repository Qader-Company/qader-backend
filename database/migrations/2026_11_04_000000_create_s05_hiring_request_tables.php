<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiring_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('reference', 32)->unique();
            $table->foreignId('created_by_member_id')->constrained('workspace_members')->restrictOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'created_at']);
            $table->index(['assigned_admin_id', 'status', 'created_at']);
        });

        Schema::create('hiring_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hiring_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('talents')->restrictOnDelete();
            $table->string('reference', 32)->unique();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->string('contract_type', 32);
            $table->boolean('interview_required');
            $table->string('status', 32)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->text('hold_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['hiring_request_id', 'talent_id']);
            $table->index(['talent_id', 'status']);
            $table->index(['hiring_request_id', 'status']);
        });

        Schema::create('hiring_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_item_id')->constrained('hiring_request_items')->cascadeOnDelete();
            $table->decimal('salary_amount', 18, 4);
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('work_type', 32);
            $table->string('work_mode', 32);
            $table->string('work_start_day', 16);
            $table->string('work_end_day', 16);
            $table->json('hybrid_work_days')->nullable();
            $table->unsignedTinyInteger('work_hours');
            $table->string('status', 16)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamps();

            $table->index(['request_item_id', 'created_at']);
            $table->index(['status', 'response_due_at']);
        });

        Schema::create('hiring_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hiring_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_item_id')->nullable()->constrained('hiring_request_items')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 64);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hiring_request_id', 'created_at']);
            $table->index(['request_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_request_events');
        Schema::dropIfExists('hiring_offers');
        Schema::dropIfExists('hiring_request_items');
        Schema::dropIfExists('hiring_requests');
    }
};
