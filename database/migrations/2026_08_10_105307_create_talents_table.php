<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('whatsapp_country_code', 5)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('phone_country_code', 5)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->foreignId('nationality_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('residence_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('residence_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->string('seniority_level')->nullable();
            $table->unsignedSmallInteger('years_of_experience')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('behance_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->unsignedTinyInteger('profile_completion_percentage')->default(0)->index();
            $table->json('profile_completion_details')->nullable();
            $table->timestamp('profile_completion_updated_at')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->date('available_from')->nullable();
            $table->boolean('willing_to_relocate')->nullable();
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->foreignId('current_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('expected_salary_min', 12, 2)->nullable();
            $table->decimal('expected_salary_max', 12, 2)->nullable();
            $table->foreignId('expected_currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talents');
    }
};
