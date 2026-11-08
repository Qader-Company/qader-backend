<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_industries', function (Blueprint $table): void {
            $table->foreignId('talent_id')
                ->constrained('talents')
                ->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['talent_id', 'industry_id']);
        });

        Schema::create('workspace_industries', function (Blueprint $table): void {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['workspace_id', 'industry_id']);
        });

        Schema::create('workspace_professional_fields', function (Blueprint $table): void {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_field_id')
                ->constrained('professional_fields')
                ->restrictOnDelete();
            $table->timestamps();
            $table->primary(['workspace_id', 'professional_field_id']);
        });

        $now = now();
        DB::table('workspaces')
            ->select(['id', 'industry_id'])
            ->orderBy('id')
            ->chunkById(500, function ($workspaces) use ($now): void {
                DB::table('workspace_industries')->insertOrIgnore(
                    $workspaces->map(fn ($workspace): array => [
                        'workspace_id' => $workspace->id,
                        'industry_id' => $workspace->industry_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                );
            });

        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('industry_id');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->foreignId('industry_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
        });

        DB::table('workspace_industries')
            ->orderBy('workspace_id')
            ->get()
            ->groupBy('workspace_id')
            ->each(function ($industries, int|string $workspaceId): void {
                DB::table('workspaces')
                    ->where('id', $workspaceId)
                    ->update(['industry_id' => $industries->first()->industry_id]);
            });

        Schema::dropIfExists('workspace_professional_fields');
        Schema::dropIfExists('workspace_industries');
        Schema::dropIfExists('talent_industries');
    }
};
