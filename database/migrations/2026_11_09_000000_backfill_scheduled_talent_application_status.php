<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Talent applications move to `scheduled` once the talent confirms an interview slot.
 * Existing applications whose latest interview is already confirmed, in progress, or completed
 * are still stored as `interview_required`; this migration aligns them with the new status.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('talent_applications')
            ->where('status', 'interview_required')
            ->whereExists(fn (Builder $query) => $query
                ->select(DB::raw(1))
                ->from('interviews')
                ->whereColumn('interviews.application_id', 'talent_applications.id')
                ->whereIn('interviews.status', ['confirmed', 'in_progress', 'completed'])
                ->whereRaw('interviews.round_number = (select max(latest.round_number) from interviews as latest where latest.application_id = talent_applications.id)'))
            ->update(['status' => 'scheduled']);
    }

    public function down(): void
    {
        DB::table('talent_applications')
            ->where('status', 'scheduled')
            ->update(['status' => 'interview_required']);
    }
};
