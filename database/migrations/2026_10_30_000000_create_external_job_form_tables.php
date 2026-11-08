<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        Schema::create('job_postings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_field_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->text('about_role');
            $table->string('work_type', 32);
            $table->string('work_mode', 32);
            $table->string('status', 16)->default('draft')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_admin_id')->constrained('admins')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('job_posting_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('section', 32);
            $table->text('content');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['job_posting_id', 'section', 'display_order'], 'job_section_order_unique');
        });

        Schema::create('job_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 16)->default('draft')->index();
            $table->json('job_details_snapshot')->nullable();
            $table->json('identity_fields_snapshot')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['job_posting_id', 'version_number']);
        });

        Schema::create('job_form_fields', function (Blueprint $table): void {
            $table->foreignId('form_version_id')->constrained('job_form_versions')->cascadeOnDelete();
            $table->foreignId('professional_form_field_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['form_version_id', 'professional_form_field_id'], 'job_form_fields_primary');
        });

        Schema::create('job_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_version_id')->constrained('job_form_versions')->restrictOnDelete();
            $table->string('applicant_name');
            $table->string('email')->index();
            $table->string('phone_country_code', 8);
            $table->string('phone_number', 24);
            $table->string('status', 32)->default('received')->index();
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('consent_at');
            $table->timestamp('submitted_at')->index();
            $table->foreignId('converted_talent_id')->nullable()->constrained('talents')->nullOnDelete();
            $table->foreignId('converted_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('job_form_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('job_form_submissions')->cascadeOnDelete();
            $table->foreignId('professional_form_field_id')->constrained()->restrictOnDelete();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'professional_form_field_id'], 'job_form_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_form_answers');
        Schema::dropIfExists('job_form_submissions');
        Schema::dropIfExists('job_form_fields');
        Schema::dropIfExists('job_form_versions');
        Schema::dropIfExists('job_posting_sections');
        Schema::dropIfExists('job_postings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
    }
};
