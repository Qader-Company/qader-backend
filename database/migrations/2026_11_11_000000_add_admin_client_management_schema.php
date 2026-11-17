<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin client management: client phone + soft delete, workspace status / CR number + soft delete,
 * and an immutable audit trail for admin actions on clients, workspaces, and workspace members.
 * Upgrade migration — existing data is preserved and all current workspaces become active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('phone_country_code', 8)->nullable()->after('user_id');
            $table->string('phone_number', 24)->nullable()->after('phone_country_code');
            $table->softDeletes();
        });

        Schema::table('workspaces', function (Blueprint $table): void {
            $table->string('status', 16)->default('active')->after('website_url');
            $table->string('cr_number', 64)->nullable()->after('is_company');
            $table->softDeletes();
            $table->index(['owner_client_id', 'status']);
        });

        Schema::create('client_management_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('workspace_member_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_admin_id')->constrained('admins')->restrictOnDelete();
            $table->string('action', 32);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'action', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_management_events');

        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropIndex(['owner_client_id', 'status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'cr_number']);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone_country_code', 'phone_number']);
        });
    }
};
