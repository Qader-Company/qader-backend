<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_field_translations')
            || Schema::hasColumn('professional_field_translations', 'description')) {
            return;
        }

        Schema::table('professional_field_translations', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        // The original professional-fields migration owns this target column.
        // Keep it when rolling back this compatibility migration.
    }
};
