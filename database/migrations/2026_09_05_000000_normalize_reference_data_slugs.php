<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'professional_fields' => 'professional-field',
            'job_titles' => 'job-title',
            'skills' => 'skill',
            'tools' => 'tool',
        ];

        foreach ($tables as $table => $fallback) {
            $this->replaceWithTemporarySlugs($table);
            $this->replaceWithPermanentSlugs($table, $fallback);
        }
    }

    public function down(): void {}

    private function replaceWithTemporarySlugs(string $table): void
    {
        DB::table($table)
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table): void {
                foreach ($records as $record) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([
                            'slug' => 'pending-migration-'.Str::uuid(),
                        ]);
                }
            });
    }

    private function replaceWithPermanentSlugs(
        string $table,
        string $fallback,
    ): void {
        if ($table === 'professional_fields') {
            $this->replaceProfessionalFieldSlugs($fallback);

            return;
        }

        DB::table($table)
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($records) use (
                $table,
                $fallback,
            ): void {
                foreach ($records as $record) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([
                            'slug' => $this->slugWithId(
                                name: $record->name,
                                id: $record->id,
                                fallback: $fallback,
                            ),
                        ]);
                }
            });
    }

    private function replaceProfessionalFieldSlugs(string $fallback): void
    {
        DB::table('professional_fields')
            ->join(
                'professional_field_translations',
                'professional_field_translations.professional_field_id',
                '=',
                'professional_fields.id',
            )
            ->where('professional_field_translations.locale', 'en')
            ->select([
                'professional_fields.id',
                'professional_field_translations.name',
            ])
            ->orderBy('professional_fields.id')
            ->get()
            ->each(function (object $record) use ($fallback): void {
                DB::table('professional_fields')
                    ->where('id', $record->id)
                    ->update([
                        'slug' => $this->slugWithId(
                            name: $record->name,
                            id: $record->id,
                            fallback: $fallback,
                        ),
                    ]);
            });
    }

    private function slugWithId(
        string $name,
        int $id,
        string $fallback,
    ): string {
        $suffix = '-'.$id;
        $baseSlug = Str::slug($name);
        $baseSlug = Str::substr(
            $baseSlug,
            0,
            255 - Str::length($suffix),
        );
        $baseSlug = rtrim($baseSlug, '-');

        return ($baseSlug !== '' ? $baseSlug : $fallback).$suffix;
    }
};
