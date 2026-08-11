<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->restrictOnDelete();
            $table->string('proficiency_level');
            $table->timestamps();
            $table->unique(['talent_id', 'language_id']);
        });

        Schema::create('talent_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['talent_id', 'skill_id']);
        });

        Schema::create('talent_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['talent_id', 'tool_id']);
        });

        Schema::create('talent_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->string('job_title');
            $table->string('company_name');
            $table->string('company_url')->nullable();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->boolean('currently_working')->default(false);
            $table->text('responsibilities')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('talent_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('talent_work_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->string('work_type');
            $table->timestamps();
            $table->unique(['talent_id', 'work_type']);
        });

        Schema::create('talent_work_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->string('work_mode');
            $table->timestamps();
            $table->unique(['talent_id', 'work_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_work_modes');
        Schema::dropIfExists('talent_work_types');
        Schema::dropIfExists('talent_projects');
        Schema::dropIfExists('talent_experiences');
        Schema::dropIfExists('talent_tools');
        Schema::dropIfExists('talent_skills');
        Schema::dropIfExists('talent_languages');
    }
};
