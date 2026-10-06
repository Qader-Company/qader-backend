<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('country_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->unique(
                ['country_id', 'locale'],
                'country_locale_unique',
            );
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->unique(
                ['city_id', 'locale'],
                'city_locale_unique',
            );
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('language_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->unique(
                ['language_id', 'locale'],
                'language_locale_unique',
            );
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('currency_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->unique(
                ['currency_id', 'locale'],
                'currency_locale_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_translations');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('tools');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('language_translations');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('city_translations');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');
    }
};
