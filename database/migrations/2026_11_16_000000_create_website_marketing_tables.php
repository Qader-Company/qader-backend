<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_faqs')) {
            Schema::create('marketing_faqs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('marketing_faq_translations')) {
            Schema::create('marketing_faq_translations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('marketing_faq_id')->constrained('marketing_faqs')->cascadeOnDelete();
                $table->string('locale', 5)->index();
                $table->string('question');
                $table->text('answer')->nullable();
                $table->unique(['marketing_faq_id', 'locale'], 'marketing_faq_locale_unique');
            });
        }

        if (! Schema::hasTable('marketing_partners')) {
            Schema::create('marketing_partners', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->text('url')->nullable();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('marketing_testimonials')) {
            Schema::create('marketing_testimonials', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('marketing_testimonial_translations')) {
            Schema::create('marketing_testimonial_translations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('marketing_testimonial_id');
                $table->foreign('marketing_testimonial_id', 'marketing_testimonial_fk')
                    ->references('id')
                    ->on('marketing_testimonials')
                    ->cascadeOnDelete();
                $table->string('locale', 5)->index();
                $table->string('name');
                $table->string('role')->nullable();
                $table->text('quote')->nullable();
                $table->unique(['marketing_testimonial_id', 'locale'], 'marketing_testimonial_locale_unique');
            });
        } elseif (! Schema::hasColumn('marketing_testimonial_translations', 'name')) {
            // Supports a partially applied earlier version where testimonial
            // names were not localized yet. The seeder fills both locales.
            Schema::table('marketing_testimonial_translations', function (Blueprint $table): void {
                $table->string('name')->nullable()->after('locale');
            });
        }

        if (! Schema::hasTable('hiring_support_requests')) {
            Schema::create('hiring_support_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone_country_code', 8);
                $table->string('phone_number', 24);
                $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
                $table->text('description');
                $table->timestamps();
                $table->index(['email', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_support_requests');
        Schema::dropIfExists('marketing_testimonial_translations');
        Schema::dropIfExists('marketing_testimonials');
        Schema::dropIfExists('marketing_partners');
        Schema::dropIfExists('marketing_faq_translations');
        Schema::dropIfExists('marketing_faqs');
    }
};
