<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->unique()->constrained('talents')->cascadeOnDelete();
            $table->string('status')->default('submitted')->index();
            $table->timestamp('submitted_at');
            $table->json('submission_snapshot')->nullable();
            $table->unsignedTinyInteger('profile_completion_at_submission')->default(0);
            $table->unsignedSmallInteger('snapshot_version')->default(1);
            $table->timestamp('snapshot_captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_applications');
    }
};
