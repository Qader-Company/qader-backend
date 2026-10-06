<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('talents', 'professional_field_id')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table
                    ->foreignId('professional_field_id')
                    ->nullable()
                    ->after('residence_city_id')
                    ->constrained('professional_fields')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('talent_applications', 'profile_snapshot')) {
            Schema::table('talent_applications', function (Blueprint $table): void {
                $table->dropColumn('profile_snapshot');
            });
        }
    }

    public function down(): void
    {
        // The canonical schema is owned by the original module migrations.
    }
};
