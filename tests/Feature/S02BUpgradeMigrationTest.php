<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class S02BUpgradeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_upgrades_the_previous_single_interview_schema_without_recreating_the_database(): void
    {
        Schema::dropIfExists('interview_evaluations');

        Schema::table('interviews', function (Blueprint $table): void {
            $table->unique('application_id');
        });

        Schema::table('interviews', function (Blueprint $table): void {
            $table->dropIndex('interviews_application_id_status_round_number_index');
            $table->dropUnique('interviews_application_id_round_number_unique');
            $table->dropColumn(['round_number', 'started_at', 'completed_at']);
        });

        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_11_01_000000_upgrade_s02b_talent_interview_completion_schema.php',
        );
        $migration->up();

        $this->assertTrue(Schema::hasColumn('interviews', 'round_number'));
        $this->assertTrue(Schema::hasColumn('interviews', 'started_at'));
        $this->assertTrue(Schema::hasColumn('interviews', 'completed_at'));
        $this->assertFalse(Schema::hasIndex('interviews', 'interviews_application_id_unique'));
        $this->assertTrue(Schema::hasIndex('interviews', 'interviews_application_id_round_number_unique'));
        $this->assertTrue(Schema::hasIndex('interviews', 'interviews_application_id_status_round_number_index'));
        $this->assertTrue(Schema::hasTable('interview_evaluations'));
    }
}
