<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeInterviews();
        $this->createInterviewEvaluations();
    }

    public function down(): void
    {
        // This migration upgrades databases that may already contain interview
        // rounds, evaluations, and automatic events. Rolling it back would
        // discard that production data or restore an invalid uniqueness rule.
    }

    private function upgradeInterviews(): void
    {
        if (! Schema::hasTable('interviews')) {
            return;
        }

        if (! Schema::hasColumn('interviews', 'round_number')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->unsignedSmallInteger('round_number')->default(1)->after('application_id');
            });
        }

        if (! Schema::hasColumn('interviews', 'started_at')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->timestamp('started_at')->nullable()->after('talent_reschedule_used_at');
            });
        }

        if (! Schema::hasColumn('interviews', 'completed_at')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            });
        }

        // Add the replacement FK-supporting index before dropping the old
        // application_id unique index; MySQL rejects the opposite order.
        if (! Schema::hasIndex('interviews', 'interviews_application_id_status_round_number_index')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->index(['application_id', 'status', 'round_number']);
            });
        }

        if (Schema::hasIndex('interviews', 'interviews_application_id_unique')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->dropUnique('interviews_application_id_unique');
            });
        }

        if (! Schema::hasIndex('interviews', 'interviews_application_id_round_number_unique')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->unique(['application_id', 'round_number']);
            });
        }
    }

    private function createInterviewEvaluations(): void
    {
        if (Schema::hasTable('interview_evaluations')) {
            return;
        }

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
    }
};
