<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->timestamps();
        });

        Schema::create('professional_field_form_field', function (Blueprint $table): void {
            $table
                ->foreignId('professional_field_id')
                ->constrained('professional_fields')
                ->cascadeOnDelete();
            $table
                ->foreignId('professional_form_field_id')
                ->constrained('professional_form_fields')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(
                ['professional_field_id', 'professional_form_field_id'],
                'professional_field_form_field_primary',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_field_form_field');
        Schema::dropIfExists('professional_form_fields');
    }
};
