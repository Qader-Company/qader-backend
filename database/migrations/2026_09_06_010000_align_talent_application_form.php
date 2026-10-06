<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('talents', 'religion')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->string('religion', 50)->nullable()->after('gender');
            });
        }

        if (Schema::hasColumn('talents', 'available_from')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->dropColumn('available_from');
            });
        }

        $obsoleteSkillColumns = collect([
            'proficiency_level',
            'years_of_experience',
            'is_primary',
        ])->filter(
            fn (string $column): bool => Schema::hasColumn('talent_skills', $column),
        )->all();

        if ($obsoleteSkillColumns !== []) {
            Schema::table('talent_skills', function (Blueprint $table) use ($obsoleteSkillColumns): void {
                $table->dropColumn($obsoleteSkillColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('talents', 'available_from')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->date('available_from')->nullable();
            });
        }

        if (Schema::hasColumn('talents', 'religion')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->dropColumn('religion');
            });
        }

        if (! Schema::hasColumn('talent_skills', 'proficiency_level')) {
            Schema::table('talent_skills', function (Blueprint $table): void {
                $table->string('proficiency_level')->nullable();
                $table->unsignedSmallInteger('years_of_experience')->nullable();
                $table->boolean('is_primary')->default(false);
            });
        }
    }
};
