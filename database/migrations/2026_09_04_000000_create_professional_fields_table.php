<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('professional_field_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_field_id')
                ->constrained('professional_fields')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unique(
                ['professional_field_id', 'locale'],
                'professional_field_locale_unique',
            );
        });

        Schema::table('talents', function (Blueprint $table): void {
            $table
                ->foreignId('professional_field_id')
                ->nullable()
                ->after('residence_city_id')
                ->constrained('professional_fields')
                ->nullOnDelete();
        });

        Schema::create('job_title_professional_field', function (Blueprint $table): void {
            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_field_id')->constrained()->cascadeOnDelete();
            $table->primary(
                ['job_title_id', 'professional_field_id'],
                'job_title_professional_field_primary',
            );
        });

        Schema::create('professional_field_skill', function (Blueprint $table): void {
            $table->foreignId('professional_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(
                ['professional_field_id', 'skill_id'],
                'professional_field_skill_primary',
            );
        });

        Schema::create('professional_field_tool', function (Blueprint $table): void {
            $table->foreignId('professional_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->primary(
                ['professional_field_id', 'tool_id'],
                'professional_field_tool_primary',
            );
        });

        $this->migrateCategories();

        Schema::table('job_titles', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::table('tools', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('professional_field_id');
        });

        Schema::table('job_titles', function (Blueprint $table): void {
            $table->string('category')->nullable()->index();
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->string('category')->nullable()->index();
        });

        Schema::table('tools', function (Blueprint $table): void {
            $table->string('category')->nullable()->index();
        });

        $this->restoreCategories();

        Schema::dropIfExists('professional_field_tool');
        Schema::dropIfExists('professional_field_skill');
        Schema::dropIfExists('job_title_professional_field');
        Schema::dropIfExists('professional_field_translations');
        Schema::dropIfExists('professional_fields');
    }

    private function migrateCategories(): void
    {
        $categories = collect(['job_titles', 'skills', 'tools'])
            ->flatMap(
                fn (string $table) => DB::table($table)
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->pluck('category')
            )
            ->unique()
            ->values();

        $now = now();

        foreach ($categories as $category) {
            DB::table('professional_fields')->updateOrInsert(
                ['slug' => Str::slug($category)],
                [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $professionalFieldId = DB::table('professional_fields')
                ->where('slug', Str::slug($category))
                ->value('id');

            DB::table('professional_field_translations')->updateOrInsert(
                [
                    'professional_field_id' => $professionalFieldId,
                    'locale' => 'en',
                ],
                ['name' => $category],
            );
        }

        $this->migrateCategoryLinks(
            table: 'job_titles',
            pivotTable: 'job_title_professional_field',
            foreignKey: 'job_title_id',
        );
        $this->migrateCategoryLinks(
            table: 'skills',
            pivotTable: 'professional_field_skill',
            foreignKey: 'skill_id',
        );
        $this->migrateCategoryLinks(
            table: 'tools',
            pivotTable: 'professional_field_tool',
            foreignKey: 'tool_id',
        );
    }

    private function migrateCategoryLinks(
        string $table,
        string $pivotTable,
        string $foreignKey,
    ): void {
        DB::table($table)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('id')
            ->each(function (object $record) use ($pivotTable, $foreignKey): void {
                $professionalFieldId = DB::table('professional_fields')
                    ->where('slug', Str::slug($record->category))
                    ->value('id');

                if ($professionalFieldId === null) {
                    return;
                }

                DB::table($pivotTable)->insertOrIgnore([
                    $foreignKey => $record->id,
                    'professional_field_id' => $professionalFieldId,
                ]);
            });
    }

    private function restoreCategories(): void
    {
        $this->restoreCategoryLinks(
            table: 'job_titles',
            pivotTable: 'job_title_professional_field',
            foreignKey: 'job_title_id',
        );
        $this->restoreCategoryLinks(
            table: 'skills',
            pivotTable: 'professional_field_skill',
            foreignKey: 'skill_id',
        );
        $this->restoreCategoryLinks(
            table: 'tools',
            pivotTable: 'professional_field_tool',
            foreignKey: 'tool_id',
        );
    }

    private function restoreCategoryLinks(
        string $table,
        string $pivotTable,
        string $foreignKey,
    ): void {
        DB::table($pivotTable)
            ->join(
                'professional_fields',
                'professional_fields.id',
                '=',
                $pivotTable.'.professional_field_id',
            )
            ->join(
                'professional_field_translations',
                function ($join): void {
                    $join
                        ->on(
                            'professional_field_translations.professional_field_id',
                            '=',
                            'professional_fields.id',
                        )
                        ->where(
                            'professional_field_translations.locale',
                            'en',
                        );
                },
            )
            ->orderBy($pivotTable.'.'.$foreignKey)
            ->orderBy('professional_field_translations.name')
            ->select([
                $pivotTable.'.'.$foreignKey.' as record_id',
                'professional_field_translations.name',
            ])
            ->get()
            ->unique('record_id')
            ->each(function (object $link) use ($table): void {
                DB::table($table)
                    ->where('id', $link->record_id)
                    ->update(['category' => $link->name]);
            });
    }
};
