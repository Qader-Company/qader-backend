<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $trashTables = [
        'admins', 'roles', 'workspace_roles', 'clients', 'talents', 'workspaces',
        'workspace_members', 'help_requests', 'job_postings', 'job_form_submissions',
        'countries', 'cities', 'currencies', 'languages', 'industries',
        'professional_fields', 'job_titles', 'skills', 'tools',
    ];

    /** @var array<int, string> */
    private array $tablesAlreadyUsingSoftDeletes = [
        'clients', 'workspaces', 'workspace_members', 'job_postings', 'job_form_submissions',
    ];

    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });

        Schema::create('platform_setting_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_admin_id')->constrained('admins')->restrictOnDelete();
            $table->string('group', 64);
            $table->json('old_values');
            $table->json('new_values');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['group', 'created_at']);
        });

        Schema::create('trash_events', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 64);
            $table->unsignedBigInteger('resource_id');
            $table->string('action', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('portal', 16)->nullable();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['resource_type', 'resource_id', 'created_at'], 'trash_resource_history_idx');
            $table->index(['action', 'created_at']);
        });

        foreach ($this->trashTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! in_array($tableName, $this->tablesAlreadyUsingSoftDeletes, true)) {
                    $table->softDeletes();
                }
                $table->timestamp('removed_from_trash_at')->nullable()->index();
            });
        }

        Schema::table('interviews', function (Blueprint $table): void {
            $table->unsignedTinyInteger('talent_reschedule_count')
                ->default(0)
                ->after('talent_reschedule_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table): void {
            $table->dropColumn('talent_reschedule_count');
        });

        foreach (array_reverse($this->trashTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropColumn('removed_from_trash_at');
                if (! in_array($tableName, $this->tablesAlreadyUsingSoftDeletes, true)) {
                    $table->dropSoftDeletes();
                }
            });
        }

        Schema::dropIfExists('trash_events');
        Schema::dropIfExists('platform_setting_events');
        Schema::dropIfExists('settings');
    }
};
