<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('interviews', 'rejected_slots_reason')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->text('rejected_slots_reason')->nullable()->after('meeting_url');
            });
        }

        if (! Schema::hasColumn('interviews', 'talent_reschedule_used_at')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->timestamp('talent_reschedule_used_at')->nullable()->after('rejected_slots_reason');
            });
        }

        if (! Schema::hasColumn('interviews', 'slot_rejection_count')) {
            Schema::table('interviews', function (Blueprint $table): void {
                $table->unsignedTinyInteger('slot_rejection_count')->default(0)->after('rejected_slots_reason');
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('interviews', 'rejected_slots_reason') ? 'rejected_slots_reason' : null,
            Schema::hasColumn('interviews', 'slot_rejection_count') ? 'slot_rejection_count' : null,
            Schema::hasColumn('interviews', 'talent_reschedule_used_at') ? 'talent_reschedule_used_at' : null,
        ]));

        if ($columns !== []) {
            Schema::table('interviews', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
