<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\HelpRequests\Domain\Models\HelpRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\Industries\Domain\Models\Industry;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-18 08:00:00');
        $this->withoutMiddleware(CheckApiKey::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_every_admin_can_view_dashboard_with_non_overlapping_hiring_summary(): void
    {
        $data = $this->dashboardData();
        Sanctum::actingAs($data['admin']->user, ['access', 'admin']);

        $this->getJson('/api/v1/admin/dashboard?period=day')
            ->assertOk()
            ->assertJsonPath('data.period.key', 'day')
            ->assertJsonPath('data.metrics.new_applications.value', 1)
            ->assertJsonPath('data.metrics.interviews.value', 1)
            ->assertJsonPath('data.metrics.talents.value', 1)
            ->assertJsonPath('data.metrics.active_clients.value', 1)
            ->assertJsonPath('data.hiring_requests.open_count', 1)
            ->assertJsonPath('data.hiring_requests.summary.in_review', 0)
            ->assertJsonPath('data.hiring_requests.summary.active', 0)
            ->assertJsonPath('data.hiring_requests.summary.on_hold', 1)
            ->assertJsonPath('data.upcoming_interviews.0.talent.id', $data['talent']->id)
            ->assertJsonPath('data.latest_clients.0.id', $data['client']->id)
            ->assertJsonPath('data.help_requests.open_count', 1)
            ->assertJsonPath('data.help_requests.unassigned_count', 1);
    }

    public function test_admin_global_search_returns_grouped_results(): void
    {
        $data = $this->dashboardData();
        Sanctum::actingAs($data['admin']->user, ['access', 'admin']);

        $this->getJson('/api/v1/admin/search?q=Dashboard')
            ->assertOk()
            ->assertJsonPath('data.query', 'Dashboard')
            ->assertJsonPath('data.requests.0.type', 'hiring_request')
            ->assertJsonPath('data.clients.0.id', $data['client']->id);
    }

    public function test_dashboard_rejects_unknown_period(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin->user, ['access', 'admin']);

        $this->getJson('/api/v1/admin/dashboard?period=quarter')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }

    public function test_dashboard_options_expose_localized_periods(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin->user, ['access', 'admin']);

        $this->getJson('/api/v1/admin/dashboard/options', ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.periods.0.value', 'day')
            ->assertJsonPath('data.periods.0.label', 'اليوم')
            ->assertJsonCount(4, 'data.periods');
    }

    private function dashboardData(): array
    {
        $admin = $this->admin();
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $industry = Industry::query()->create([
            'slug' => 'technology',
            'is_active' => true,
            'en' => ['name' => 'Technology'],
            'ar' => ['name' => 'التكنولوجيا'],
        ]);
        $clientUser = $this->user('client@example.test', PortalTypeEnum::CLIENT);
        $client = Client::query()->create(['user_id' => $clientUser->id]);
        $workspace = Workspace::query()->create([
            'owner_client_id' => $client->id,
            'name' => 'Dashboard Workspace',
            'description' => 'Dashboard test workspace.',
            'is_company' => true,
            'country_id' => $country->id,
            'size_band' => '11-50',
        ]);
        $workspace->industries()->attach($industry);
        $member = WorkspaceMember::query()->create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $jobTitle = JobTitle::query()->create(['name' => 'Backend Developer', 'is_active' => true]);
        $talentUser = $this->user('talent@example.test', PortalTypeEnum::TALENT);
        $talent = Talent::query()->create([
            'user_id' => $talentUser->id,
            'job_title_id' => $jobTitle->id,
        ]);
        TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $hiringRequest = HiringRequest::query()->create([
            'workspace_id' => $workspace->id,
            'reference' => 'HR-DASHBOARD',
            'created_by_member_id' => $member->id,
            'assigned_admin_id' => $admin->id,
            'status' => 'active',
            'submitted_at' => now(),
            'started_at' => now(),
        ]);
        $item = HiringRequestItem::query()->create([
            'hiring_request_id' => $hiringRequest->id,
            'talent_id' => $talent->id,
            'reference' => 'RI-DASHBOARD',
            'job_title_id' => $jobTitle->id,
            'contract_type' => 'managed',
            'interview_required' => true,
            'status' => 'on_hold',
            'hold_reason' => 'Waiting for client.',
        ]);
        HiringInterview::query()->create([
            'request_item_id' => $item->id,
            'status' => 'confirmed',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'proposed_by_admin_id' => $admin->id,
            'proposed_at' => now(),
            'confirmed_by_admin_id' => $admin->id,
            'confirmed_at' => now(),
        ]);
        HelpRequest::query()->create([
            'workspace_id' => $workspace->id,
            'created_by_member_id' => $member->id,
            'help_type' => 'hiring_support',
            'description' => 'Need help hiring a backend developer.',
            'contact_method' => 'email',
            'contact_email' => 'client@example.test',
            'status' => 'new',
        ]);

        return compact('admin', 'client', 'talent');
    }

    private function admin(): Admin
    {
        $user = $this->user('admin@example.test', PortalTypeEnum::ADMIN);

        return Admin::query()->create(['user_id' => $user->id]);
    }

    private function user(string $email, PortalTypeEnum $type): User
    {
        return User::query()->create([
            'first_name' => ucfirst(strstr($email, '@', true)),
            'last_name' => 'User',
            'email' => $email,
            'password' => 'Password123!',
            'type' => $type,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
