<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentBlacklistEntry;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientTalentMarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private ProfessionalField $field;

    private JobTitle $jobTitle;

    private Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $this->field = ProfessionalField::query()->create([
            'is_active' => true,
            'en' => ['name' => 'Software Development'],
            'ar' => ['name' => 'تطوير البرمجيات'],
        ]);
        $this->jobTitle = JobTitle::query()->create([
            'name' => 'Backend Developer',
            'is_active' => true,
        ]);
        $this->skill = Skill::query()->create([
            'name' => 'Laravel',
            'is_active' => true,
        ]);

        $this->owner = $this->clientUser('owner@example.test');
        $this->workspace = Workspace::query()->create([
            'owner_client_id' => $this->owner->client->id,
            'name' => 'Owner Workspace',
            'description' => 'Workspace used for marketplace tests.',
            'is_company' => true,
            'country_id' => $country->id,
            'size_band' => '11-50',
        ]);
        $this->workspace->professionalFields()->attach($this->field);

        $member = WorkspaceMember::query()->create([
            'workspace_id' => $this->workspace->id,
            'client_id' => $this->owner->client->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $role = WorkspaceRole::query()->create([
            'workspace_id' => $this->workspace->id,
            'key' => 'owner',
            'name_en' => 'Owner',
            'name_ar' => 'المالك',
            'is_active' => true,
        ]);
        $member->roles()->attach($role);

        Sanctum::actingAs($this->owner, ['access', 'client']);
    }

    public function test_marketplace_only_returns_eligible_anonymous_talents(): void
    {
        $accepted = $this->talent('accepted@example.test', true, true);
        $this->talent('rejected@example.test', false, true);
        $this->talent('inactive@example.test', true, false);
        $blacklisted = $this->talent('blacklisted@example.test', true, true);
        $this->blacklist($blacklisted);

        $response = $this->getJson($this->marketplaceUrl());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $accepted->id)
            ->assertJsonPath('data.data.0.talent_code', $accepted->talent_code)
            ->assertJsonPath('data.data.0.job_title.name', 'Backend Developer')
            ->assertJsonPath('data.data.0.top_skills.0.name', 'Laravel')
            ->assertJsonPath('data.data.0.is_selected', false)
            ->assertJsonPath('data.data.0.available_actions.0', 'view_profile')
            ->assertJsonPath('data.data.0.available_actions.1', 'add_to_squad');

        $this->assertStringNotContainsString('accepted@example.test', $response->getContent());
        $this->assertStringNotContainsString('PrivateAccepted', $response->getContent());

        $details = $this->getJson($this->marketplaceUrl('/'.$accepted->id));
        $details->assertOk()
            ->assertJsonPath('data.id', $accepted->id)
            ->assertJsonPath('data.expert_opinion', null)
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.first_name')
            ->assertJsonMissingPath('data.phone_number')
            ->assertJsonMissingPath('data.cv');
    }

    public function test_owner_can_build_a_workspace_scoped_squad_without_duplicates(): void
    {
        $talent = $this->talent('squad@example.test', true, true);

        $this->postJson($this->selectionUrl(), ['talent_id' => $talent->id])
            ->assertCreated()
            ->assertJsonPath('data.id', $talent->id)
            ->assertJsonPath('data.is_selected', true)
            ->assertJsonPath('data.available_actions.1', 'remove_from_squad');

        $this->postJson($this->selectionUrl(), ['talent_id' => $talent->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('talent_id');

        $this->getJson($this->selectionUrl())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $talent->id);

        $this->deleteJson($this->selectionUrl('/'.$talent->id))
            ->assertOk();
        $this->assertDatabaseMissing('workspace_talent_selections', [
            'workspace_id' => $this->workspace->id,
            'talent_id' => $talent->id,
        ]);
    }

    public function test_salary_filters_require_currency_but_currency_can_be_used_alone(): void
    {
        $this->getJson($this->marketplaceUrl().'?salary_min=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');

        $this->getJson($this->marketplaceUrl().'?currency_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id')
            ->assertJsonMissingValidationErrors(['salary_min', 'salary_max']);
    }

    public function test_workspace_membership_is_not_leaked_to_outsiders(): void
    {
        $outsider = $this->clientUser('outsider@example.test');
        Sanctum::actingAs($outsider, ['access', 'client']);

        $this->getJson($this->marketplaceUrl())->assertNotFound();
    }

    private function talent(string $email, bool $accepted, bool $active): Talent
    {
        $user = User::query()->create([
            'first_name' => 'PrivateAccepted',
            'last_name' => 'Talent',
            'email' => $email,
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => $active,
            'email_verified_at' => now(),
        ]);
        $talent = Talent::query()->create([
            'user_id' => $user->id,
            'professional_field_id' => $this->field->id,
            'job_title_id' => $this->jobTitle->id,
            'seniority_level' => SeniorityLevelEnum::SENIOR,
            'years_of_experience' => 7,
            'professional_summary' => 'Experienced backend engineer.',
            'phone_country_code' => '+20',
            'phone_number' => '1000000000',
            'profile_completion_percentage' => 100,
            'onboarding_completed_at' => now(),
        ]);
        $talent->skills()->attach($this->skill);
        TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'status' => $accepted
                ? TalentApplicationStatusEnum::ACCEPTED
                : TalentApplicationStatusEnum::REJECTED,
            'submitted_at' => now()->subDay(),
            'review_decided_at' => now(),
            'profile_completion_at_submission' => 100,
        ]);

        return $talent->refresh();
    }

    private function blacklist(Talent $talent): void
    {
        $adminUser = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'blacklist-admin@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin = Admin::query()->create(['user_id' => $adminUser->id]);
        $application = TalentApplication::query()->where('talent_id', $talent->id)->firstOrFail();
        TalentBlacklistEntry::query()->create([
            'talent_id' => $talent->id,
            'application_id' => $application->id,
            'blacklist_reason' => 'Test blacklist entry.',
            'blocked_by_admin_id' => $admin->id,
            'blocked_at' => now(),
        ]);
    }

    private function clientUser(string $email): User
    {
        $user = User::query()->create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password123!',
            'type' => PortalTypeEnum::CLIENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        Client::query()->create(['user_id' => $user->id]);

        return $user->load('client');
    }

    private function marketplaceUrl(string $suffix = ''): string
    {
        return "/api/v1/client/workspaces/{$this->workspace->id}/talent-marketplace{$suffix}";
    }

    private function selectionUrl(string $suffix = ''): string
    {
        return "/api/v1/client/workspaces/{$this->workspace->id}/talent-selections{$suffix}";
    }
}
