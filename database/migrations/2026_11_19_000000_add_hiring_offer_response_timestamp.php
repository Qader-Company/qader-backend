<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->timestamp('responded_at')->nullable()->after('response_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('hiring_offers', function (Blueprint $table): void {
            $table->dropColumn('responded_at');
        });
    }
};
