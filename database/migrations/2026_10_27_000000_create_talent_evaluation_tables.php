<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addApplicationReviewColumns();

        Schema::create('talent_application_reviewer_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('reviewer_admin_id');
            $table->unsignedBigInteger('assigned_by_admin_id');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreign('application_id', 'reviewer_assignments_application_fk')->references('id')->on('talent_applications')->cascadeOnDelete();
            $table->foreign('reviewer_admin_id', 'reviewer_assignments_reviewer_fk')->references('id')->on('admins')->restrictOnDelete();
            $table->foreign('assigned_by_admin_id', 'reviewer_assignments_assigner_fk')->references('id')->on('admins')->restrictOnDelete();
            $table->index(['application_id', 'ended_at'], 'reviewer_assignments_application_active_idx');
            $table->index(['reviewer_admin_id', 'ended_at'], 'reviewer_assignments_reviewer_active_idx');
        });

        Schema::create('talent_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('talent_applications')->cascadeOnDelete();
            $table->foreignId('evaluator_admin_id')->constrained('admins')->restrictOnDelete();
            $table->unsignedTinyInteger('technical_skills_rating');
            $table->unsignedTinyInteger('communication_skills_rating');
            $table->unsignedTinyInteger('problem_solving_rating');
            $table->unsignedTinyInteger('soft_skills_rating');
            $table->decimal('recommended_salary_min', 18, 4)->nullable();
            $table->decimal('recommended_salary_max', 18, 4)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->restrictOnDelete();
            $table->text('hr_report')->nullable();
            $table->text('technical_report')->nullable();
            $table->text('psychology_report')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluation_tips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('talent_evaluations')->cascadeOnDelete();
            $table->string('title');
            $table->string('priority', 16);
            $table->text('description');
            $table->unsignedSmallInteger('display_order')->default(0);
        });

        Schema::create('talent_blacklist_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->restrictOnDelete();
            $table->foreignId('application_id')->unique()->constrained('talent_applications')->restrictOnDelete();
            $table->text('blacklist_reason');
            $table->foreignId('blocked_by_admin_id')->constrained('admins')->restrictOnDelete();
            $table->timestamp('blocked_at');
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_admin_id')->nullable()->constrained('admins')->restrictOnDelete();
            $table->index(['talent_id', 'removed_at']);
        });

        Schema::create('interviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('talent_applications')->restrictOnDelete();
            $table->unsignedSmallInteger('round_number')->default(1);
            $table->string('type', 32);
            $table->string('status', 32)->default('awaiting_slot_selection')->index();
            $table->foreignId('created_by_admin_id')->constrained('admins')->restrictOnDelete();
            $table->string('google_calendar_event_id')->nullable()->unique();
            $table->text('meeting_url')->nullable();
            $table->text('rejected_slots_reason')->nullable();
            $table->unsignedTinyInteger('slot_rejection_count')->default(0);
            $table->timestamp('talent_reschedule_used_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'round_number']);
            $table->index(['application_id', 'status', 'round_number']);
        });

        Schema::create('interview_admin', function (Blueprint $table): void {
            $table->foreignId('interview_id')->constrained('interviews')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->primary(['interview_id', 'admin_id']);
            $table->index(['admin_id', 'interview_id']);
        });

        Schema::create('interview_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_id')->constrained('interviews')->cascadeOnDelete();
            $table->string('mode', 16);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('location', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 32)->default('available');
            $table->timestamps();
            $table->unique(['interview_id', 'starts_at']);
            $table->index(['starts_at', 'ends_at', 'status']);
        });

        Schema::create('interview_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_id')->unique()->constrained('interviews')->cascadeOnDelete();
            $table->foreignId('evaluator_admin_id')->constrained('admins')->restrictOnDelete();
            $table->unsignedTinyInteger('technical_skills_rating');
            $table->unsignedTinyInteger('communication_skills_rating');
            $table->unsignedTinyInteger('problem_solving_rating');
            $table->unsignedTinyInteger('soft_skills_rating');
            $table->text('hr_report')->nullable();
            $table->text('technical_report')->nullable();
            $table->text('psychology_report')->nullable();
            $table->text('final_feedback');
            $table->timestamps();
        });

        Schema::create('talent_application_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('talent_applications')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->string('action', 64);
            $table->json('data')->nullable();
            $table->timestamp('created_at');
            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_application_events');
        Schema::dropIfExists('interview_evaluations');
        Schema::dropIfExists('interview_slots');
        Schema::dropIfExists('interview_admin');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('talent_blacklist_entries');
        Schema::dropIfExists('evaluation_tips');
        Schema::dropIfExists('talent_evaluations');
        Schema::dropIfExists('talent_application_reviewer_assignments');
    }

    private function addApplicationReviewColumns(): void
    {
        if (! Schema::hasColumn('talent_applications', 'assigned_admin_id')) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->foreignId('assigned_admin_id')->nullable()->after('talent_id')->constrained('admins')->nullOnDelete();
                $table->index(['assigned_admin_id', 'status']);
            });
        }

        if (! Schema::hasColumn('talent_applications', 'review_decided_by_admin_id')) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->foreignId('review_decided_by_admin_id')->nullable()->after('assigned_admin_id')->constrained('admins')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('talent_applications', 'review_decided_at')) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->timestamp('review_decided_at')->nullable()->after('review_decided_by_admin_id');
            });
        }

        if (! Schema::hasColumn('talent_applications', 'reapply_at')) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->timestamp('reapply_at')->nullable()->after('review_decided_at')->index();
            });
        }

        if (
            Schema::hasColumn('talent_applications', 'submitted_at')
            && ! Schema::hasIndex('talent_applications', ['status', 'submitted_at'])
        ) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->index(['status', 'submitted_at']);
            });
        }
    }
};
