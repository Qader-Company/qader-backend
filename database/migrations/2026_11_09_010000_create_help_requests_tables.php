<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_requests', function (Blueprint $table): void {
            $table->id();
            // Sequential REQ-{1000 + id}; filled right after insert.
            $table->string('reference', 32)->nullable()->unique();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_member_id')->constrained('workspace_members')->restrictOnDelete();
            $table->string('help_type', 32);
            $table->text('description')->nullable();
            $table->string('contact_method', 16);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone_country_code', 8)->nullable();
            $table->string('contact_phone_number', 32)->nullable();
            $table->string('status', 16)->default('new');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('closure_outcome', 32)->nullable();
            $table->text('closure_details')->nullable();
            $table->foreignId('resulting_hiring_request_id')->nullable()->constrained('hiring_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_admin_id', 'status', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
            $table->index('help_type');
        });

        Schema::create('help_request_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('help_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['help_request_id', 'created_at']);
        });

        Schema::create('help_request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('help_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 64);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['help_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_request_events');
        Schema::dropIfExists('help_request_notes');
        Schema::dropIfExists('help_requests');
    }
};
