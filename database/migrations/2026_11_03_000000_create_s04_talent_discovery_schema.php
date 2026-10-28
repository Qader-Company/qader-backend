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
        if (! Schema::hasColumn('talents', 'talent_code')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->string('talent_code', 32)->nullable()->unique()->after('id');
            });

            DB::table('talents')
                ->select('id')
                ->orderBy('id')
                ->chunkById(500, function ($talents): void {
                    foreach ($talents as $talent) {
                        DB::table('talents')
                            ->where('id', $talent->id)
                            ->update(['talent_code' => $this->uniqueTalentCode()]);
                    }
                });

            Schema::table('talents', function (Blueprint $table): void {
                $table->string('talent_code', 32)->nullable(false)->change();
            });
        }

        Schema::create('workspace_talent_selections', function (Blueprint $table): void {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('added_by_member_id')
                ->constrained('workspace_members')
                ->restrictOnDelete();
            $table->timestamps();

            $table->primary(['workspace_id', 'talent_id']);
            $table->index(['talent_id', 'workspace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_talent_selections');

        if (Schema::hasColumn('talents', 'talent_code')) {
            Schema::table('talents', function (Blueprint $table): void {
                $table->dropColumn('talent_code');
            });
        }
    }

    private function uniqueTalentCode(): string
    {
        do {
            $code = 'QDR-'.Str::upper(Str::random(8));
        } while (DB::table('talents')->where('talent_code', $code)->exists());

        return $code;
    }
};
