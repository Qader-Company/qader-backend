<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_field_translations', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('professional_field_form_field', function (Blueprint $table): void {
            $table->dropColumn('is_required');
        });

        Schema::table('professional_form_fields', function (Blueprint $table): void {
            $table->dropIndex(['step']);
            $table->dropColumn(['step', 'is_default', 'is_required_by_default']);
        });

        Schema::table('talent_applications', function (Blueprint $table): void {
            $table->json('submission_snapshot')->nullable()->after('submitted_at');
            $table->unsignedTinyInteger('profile_completion_at_submission')->default(0)->after('submission_snapshot');
            $table->unsignedSmallInteger('snapshot_version')->default(1)->after('profile_completion_at_submission');
            $table->timestamp('snapshot_captured_at')->nullable()->after('snapshot_version');
        });
    }

    public function down(): void
    {
        Schema::table('talent_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'submission_snapshot',
                'profile_completion_at_submission',
                'snapshot_version',
                'snapshot_captured_at',
            ]);
        });

        Schema::table('professional_form_fields', function (Blueprint $table): void {
            $table->string('step')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_required_by_default')->default(false);
        });

        Schema::table('professional_field_form_field', function (Blueprint $table): void {
            $table->boolean('is_required')->default(false);
        });

        Schema::table('professional_field_translations', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
