<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->uuid('token_family_id')->nullable()->after('tokenable_id')->index();
            $table->foreignId('user_device_id')
                ->nullable()
                ->after('token_family_id')
                ->constrained('user_devices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_device_id');
            $table->dropColumn('token_family_id');
        });
    }
};
