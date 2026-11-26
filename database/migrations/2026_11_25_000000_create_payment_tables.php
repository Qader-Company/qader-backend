<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->uuid('idempotency_key');
            $table->char('request_fingerprint', 64);
            $table->foreignId('payer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('owner_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->foreignId('talent_id')->nullable()->constrained('talents')->restrictOnDelete();
            $table->foreignId('talent_application_id')->nullable()->constrained('talent_applications')->restrictOnDelete();
            $table->string('service', 64);
            $table->decimal('amount', 18, 4);
            $table->char('currency', 3);
            $table->string('status', 24);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payer_user_id', 'idempotency_key'], 'payments_payer_idempotency_unique');
            $table->index(['owner_client_id', 'talent_id', 'service', 'status'], 'payments_client_access_index');
            $table->index(['payer_user_id', 'talent_application_id', 'service', 'status'], 'payments_talent_access_index');
        });

        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->uuid('gateway_request_id')->unique();
            $table->string('provider', 32);
            $table->string('provider_reference')->nullable();
            $table->string('attempt_status', 24);
            $table->string('checkout_mode', 16)->nullable();
            $table->text('checkout_url')->nullable();
            $table->longText('client_token')->nullable();
            $table->json('provider_data')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference'], 'payment_attempts_provider_reference_unique');
            $table->index(['payment_id', 'attempt_status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_attempt_id')->constrained('payment_attempts')->cascadeOnDelete();
            $table->string('event_id');
            $table->string('event_type', 100);
            $table->longText('payload');
            $table->string('processing_status', 24);
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->unique(['payment_attempt_id', 'event_id'], 'payment_webhook_attempt_event_unique');
            $table->index(['processing_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payments');
    }
};
