<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter([
            'talent_decision',
            'talent_decision_reason',
            'talent_decided_at',
        ], fn (string $column): bool => Schema::hasColumn('hiring_request_items', $column)));

        if ($columns !== []) {
            Schema::table('hiring_request_items', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        $addDecision = ! Schema::hasColumn('hiring_request_items', 'talent_decision');
        $addReason = ! Schema::hasColumn('hiring_request_items', 'talent_decision_reason');
        $addDecidedAt = ! Schema::hasColumn('hiring_request_items', 'talent_decided_at');

        Schema::table('hiring_request_items', function (Blueprint $table) use ($addDecision, $addReason, $addDecidedAt): void {
            if ($addDecision) {
                $table->string('talent_decision', 16)->nullable()->after('client_decided_at');
            }
            if ($addReason) {
                $table->text('talent_decision_reason')->nullable()->after('talent_decision');
            }
            if ($addDecidedAt) {
                $table->timestamp('talent_decided_at')->nullable()->after('talent_decision_reason');
            }
        });
    }
};
