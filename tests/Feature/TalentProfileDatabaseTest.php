<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentProfileDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_same_email_can_be_used_by_different_user_types(): void
    {
        User::factory()->create([
            'email' => 'ahmed@example.com',
            'type' => UserType::Talent,
        ]);

        $client = User::factory()->create([
            'email' => 'ahmed@example.com',
            'type' => UserType::Client,
        ]);

        $this->assertSame(UserType::Client, $client->type);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_an_email_cannot_be_reused_for_the_same_user_type(): void
    {
        User::factory()->create([
            'email' => 'ahmed@example.com',
            'type' => UserType::Talent,
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'email' => 'ahmed@example.com',
            'type' => UserType::Talent,
        ]);
    }

    public function test_a_user_can_have_only_one_talent_profile(): void
    {
        $user = User::factory()->create();

        Talent::create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        Talent::create(['user_id' => $user->id]);
    }

    public function test_password_reset_tokens_are_scoped_to_the_user_type(): void
    {
        DB::table('password_reset_tokens')->insert([
            [
                'email' => 'ahmed@example.com',
                'type' => UserType::Talent->value,
                'token' => 'talent-token',
            ],
            [
                'email' => 'ahmed@example.com',
                'type' => UserType::Client->value,
                'token' => 'client-token',
            ],
        ]);

        $this->assertDatabaseCount('password_reset_tokens', 2);
    }
}
