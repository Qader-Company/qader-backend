<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('industry_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->timestamps();
            $table->unique(['industry_id', 'locale']);
        });

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_client_id')->constrained('clients')->restrictOnDelete();
            $table->string('name');
            $table->foreignId('industry_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->boolean('is_company');
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('size_band', 16);
            $table->string('website_url', 2048)->nullable();
            $table->timestamps();
            $table->index('owner_client_id');
        });

        Schema::create('workspace_social_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 32);
            $table->string('url', 2048);
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('active');
            $table->timestamp('joined_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['workspace_id', 'client_id']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('workspace_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['workspace_id', 'key']);
        });

        Schema::create('workspace_role_permissions', function (Blueprint $table): void {
            $table->foreignId('workspace_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->restrictOnDelete();
            $table->primary(['workspace_role_id', 'permission_id']);
        });

        Schema::create('workspace_member_roles', function (Blueprint $table): void {
            $table->foreignId('workspace_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_role_id')->constrained()->cascadeOnDelete();
            $table->primary(['workspace_member_id', 'workspace_role_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_member_roles');
        Schema::dropIfExists('workspace_role_permissions');
        Schema::dropIfExists('workspace_roles');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspace_social_links');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('industry_translations');
        Schema::dropIfExists('industries');
    }
};
