<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_review_faqs', function (Blueprint $table): void {
            $table->id();
            $table->json('screen_states');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('talent_review_faq_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('talent_review_faq_id')->constrained('talent_review_faqs')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('question');
            $table->text('answer');
            $table->unique(['talent_review_faq_id', 'locale'], 'talent_review_faq_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_review_faq_translations');
        Schema::dropIfExists('talent_review_faqs');
    }
};
