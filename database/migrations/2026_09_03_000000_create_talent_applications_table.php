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
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('review_decided_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('review_decided_at')->nullable();
            $table->timestamp('reapply_at')->nullable()->index();
            $table->timestamps();

            $table->index(['assigned_admin_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_applications');
    }
};
