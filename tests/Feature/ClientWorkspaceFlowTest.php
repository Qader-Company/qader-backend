<?php

namespace Tests\Feature;

use App\Modules\V1\AccessControl\Application\Services\WorkspaceRoleService;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Application\Mail\WorkspaceMemberAccountMail;
use App\Modules\V1\Workspaces\Application\Services\WorkspaceService;
use App\Modules\V1\Workspaces\Application\Services\WorkspaceTeamService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientWorkspaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_workspace_and_adds_member_with_multiple_roles(): void
    {
        Mail::fake();
        $this->seed(PermissionSeeder::class);
        $industry = Industry::query()->create(['slug' => 'technology', 'is_active' => true, 'en' => ['name' => 'Technology'], 'ar' => ['name' => 'التكنولوجيا']]);
        $secondIndustry = Industry::query()->create(['slug' => 'financial-services', 'is_active' => true, 'en' => ['name' => 'Financial Services'], 'ar' => ['name' => 'الخدمات المالية']]);
        $professionalField = ProfessionalField::query()->create(['is_active' => true, 'en' => ['name' => 'Software Development'], 'ar' => ['name' => 'تطوير البرمجيات']]);
        $secondProfessionalField = ProfessionalField::query()->create(['is_active' => true, 'en' => ['name' => 'Design'], 'ar' => ['name' => 'التصميم']]);
        $country = Country::query()->create(['code' => 'EG', 'is_active' => true, 'en' => ['name' => 'Egypt'], 'ar' => ['name' => 'مصر']]);
        $city = City::query()->create(['country_id' => $country->id, 'is_active' => true, 'en' => ['name' => 'Cairo'], 'ar' => ['name' => 'القاهرة']]);
        $owner = $this->clientUser('owner@example.test');
        $workspaceService = app(WorkspaceService::class);
        $workspace = $workspaceService->create($owner, ['name' => 'Qader Client', 'industry_ids' => [$industry->id, $secondIndustry->id], 'professional_field_ids' => [$professionalField->id, $secondProfessionalField->id], 'description' => 'Workspace description', 'is_company' => true, 'country_id' => $country->id, 'city_id' => $city->id, 'size_band' => '11-50', 'website_url' => 'https://example.com', 'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/example']]]);
        $otherCountry = Country::query()->create(['code' => 'SA', 'is_active' => true, 'en' => ['name' => 'Saudi Arabia'], 'ar' => ['name' => 'السعودية']]);
        $workspace = $workspaceService->update($owner, $workspace, ['country_id' => $otherCountry->id]);
        $this->assertNull($workspace->city_id);
        $this->assertEqualsCanonicalizing([$industry->id, $secondIndustry->id], $workspace->industries->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$professionalField->id, $secondProfessionalField->id], $workspace->professionalFields->pluck('id')->all());
        $roles = app(WorkspaceRoleService::class);
        $manager = $roles->createRole($owner, $workspace, ['name_en' => 'Manager', 'name_ar' => 'مدير', 'permissions' => ['client.members.view', 'client.members.create']]);
        $viewer = $roles->createRole($owner, $workspace, ['name_en' => 'Viewer', 'name_ar' => 'مشاهد', 'permissions' => ['client.workspace.view']]);
        $team = app(WorkspaceTeamService::class);
        $member = $team->createMember($owner, $workspace, ['first_name' => 'Team', 'last_name' => 'Member', 'email' => 'member@example.test', 'role_ids' => [$manager->id, $viewer->id]]);
        Mail::assertQueued(WorkspaceMemberAccountMail::class, fn (WorkspaceMemberAccountMail $mail) => $mail->email === 'member@example.test' && $mail->temporaryPassword !== null && ! $mail->existingAccount);
        $this->assertCount(2, $member->roles);
        $this->assertSame('active', $member->status->value);
        $this->assertTrue($member->client->user->must_change_password);
        $this->assertDatabaseHas('workspace_members', ['workspace_id' => $workspace->id, 'client_id' => $member->client_id, 'deleted_at' => null]);

        $outsider = $this->clientUser('outsider@example.test');
        $this->expectException(ModelNotFoundException::class);
        $workspaceService->show($outsider, $workspace);
    }

    private function clientUser(string $email): User
    {
        $user = User::query()->create(['first_name' => 'Owner', 'last_name' => 'Client', 'email' => $email, 'password' => 'Password123!', 'type' => PortalTypeEnum::CLIENT, 'is_active' => true, 'email_verified_at' => now()]);
        Client::query()->create(['user_id' => $user->id]);

        return $user->load('client');
    }
}
