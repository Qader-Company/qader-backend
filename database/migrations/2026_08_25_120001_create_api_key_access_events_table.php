<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_access_events', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('api_key_id');
            $table->ipAddress('ip_address');
            $table->text('url');
            $table->timestamps();
            $table->foreign('api_key_id')->references('id')->on('api_keys');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_access_events');
    }
};
