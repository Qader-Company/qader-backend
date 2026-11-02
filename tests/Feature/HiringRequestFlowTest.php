<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Currencies\Domain\Models\Currency;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferExpirationService;
use App\Modules\V1\HiringRequests\Domain\Models\HiringInterview;
use App\Modules\V1\HiringRequests\Domain\Models\HiringOffer;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceTalentSelection;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HiringRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private WorkspaceMember $member;

    private Talent $talent;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        $this->seed([PermissionSeeder::class, AdminRoleSeeder::class]);

        $country = Country::query()->create([
            'code' => 'EG', 'is_active' => true,
            'en' => ['name' => 'Egypt'], 'ar' => ['name' => 'مصر'],
        ]);
        $field = ProfessionalField::query()->create([
            'is_active' => true,
            'en' => ['name' => 'Software'], 'ar' => ['name' => 'برمجيات'],
        ]);
        $jobTitle = JobTitle::query()->create(['name' => 'Backend Developer', 'is_active' => true]);
        $this->currency = Currency::query()->create([
            'code' => 'USD', 'symbol' => '$', 'is_active' => true,
            'en' => ['name' => 'US Dollar'], 'ar' => ['name' => 'دولار أمريكي'],
        ]);

        $this->owner = $this->clientUser('owner-hiring@example.test');
        $this->workspace = Workspace::query()->create([
            'owner_client_id' => $this->owner->client->id,
            'name' => 'Hiring Workspace',
            'description' => 'Hiring request test workspace.',
            'is_company' => true,
            'country_id' => $country->id,
            'size_band' => '11-50',
        ]);
        $this->member = WorkspaceMember::query()->create([
            'workspace_id' => $this->workspace->id,
            'client_id' => $this->owner->client->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $ownerRole = WorkspaceRole::query()->create([
            'workspace_id' => $this->workspace->id,
            'key' => 'owner',
            'name_en' => 'Owner',
            'name_ar' => 'المالك',
            'is_active' => true,
        ]);
        $this->member->roles()->attach($ownerRole);

        $talentUser = User::query()->create([
            'first_name' => 'Hidden', 'last_name' => 'Talent',
            'email' => 'hiring-talent@example.test', 'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->talent = Talent::query()->create([
            'user_id' => $talentUser->id,
            'professional_field_id' => $field->id,
            'job_title_id' => $jobTitle->id,
            'notice_period_days' => 30,
        ]);
        TalentApplication::query()->create([
            'talent_id' => $this->talent->id,
            'status' => TalentApplicationStatusEnum::ACCEPTED,
            'submitted_at' => now()->subDay(),
            'review_decided_at' => now(),
            'profile_completion_at_submission' => 100,
        ]);
        $this->selectTalent();
        Sanctum::actingAs($this->owner, ['access', 'client']);
    }

    public function test_client_creates_and_submits_a_private_workspace_request(): void
    {
        $draft = $this->postJson($this->clientUrl(), $this->draftBody())
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.items.0.offer.notice_period_days', 30)
            ->assertJsonMissingPath('data.items.0.talent.full_name')
            ->json('data');

        $this->postJson($this->clientUrl('/'.$draft['id'].'/submit'))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.status', 'pending');

        $this->assertDatabaseMissing('workspace_talent_selections', [
            'workspace_id' => $this->workspace->id,
            'talent_id' => $this->talent->id,
        ]);
        $this->assertDatabaseHas('hiring_request_events', [
            'hiring_request_id' => $draft['id'],
            'action' => 'submitted',
        ]);
    }

    public function test_same_talent_cannot_be_submitted_twice_while_request_is_active(): void
    {
        $first = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$first}/submit"))->assertOk();

        $this->selectTalent();
        $second = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$second}/submit"))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseHas('workspace_talent_selections', [
            'workspace_id' => $this->workspace->id,
            'talent_id' => $this->talent->id,
        ]);
    }

    public function test_responsible_admin_starts_request_and_sends_offer_with_fixed_deadline(): void
    {
        $id = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$id}/submit"))->assertOk();

        $admin = $this->adminUser('hiring-admin@example.test');
        Sanctum::actingAs($admin->user, ['access', 'admin']);

        $this->postJson("/api/v1/admin/hiring-requests/{$id}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.items.0.status', 'awaiting_talent_response')
            ->assertJsonPath('data.items.0.offer.is_overdue', false)
            ->assertJsonPath('data.available_actions', ['reassign']);

        $request = HiringRequest::query()->with('items.latestOffer')->findOrFail($id);
        $offer = $request->items->first()->latestOffer;
        $this->assertSame($admin->id, $request->assigned_admin_id);
        $this->assertSame(HiringRequestStatusEnum::ACTIVE, $request->status);
        $this->assertSame(HiringRequestItemStatusEnum::AWAITING_TALENT_RESPONSE, $request->items->first()->status);
        $this->assertNotNull($offer->sent_at);
        $this->assertTrue($offer->sent_at->copy()->addHours(48)->equalTo($offer->response_due_at));
    }

    public function test_s06_offer_interview_and_both_decisions_reach_ready_to_proceed(): void
    {
        $requestId = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$requestId}/submit"))->assertOk();
        $admin = $this->adminUser('s06-admin@example.test');
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $started = $this->postJson("/api/v1/admin/hiring-requests/{$requestId}/start")->assertOk()->json('data');
        $itemId = $started['items'][0]['id'];

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-offers/{$itemId}/accept")
            ->assertOk()->assertJsonPath('data.status', 'awaiting_interview_slots');

        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $interview = $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/schedule", [
            'starts_at' => now()->addDay()->toIso8601String(),
        ])->assertOk()->assertJsonPath('data.status', 'awaiting_responses')->json('data');

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-interviews/{$interview['id']}/accept-schedule")
            ->assertOk()->assertJsonPath('data.talent_response.status', 'accepted');

        Sanctum::actingAs($this->owner, ['access', 'client']);
        $this->postJson("/api/v1/client/workspaces/{$this->workspace->id}/hiring-interviews/{$interview['id']}/accept-schedule")
            ->assertOk()->assertJsonPath('data.status', 'awaiting_admin_confirmation');

        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/confirm", [
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ])->assertOk()->assertJsonPath('data.status', 'confirmed');

        HiringInterview::query()->where('request_item_id', $itemId)->update([
            'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour(),
        ]);
        $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/start")
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/complete")
            ->assertOk()->assertJsonPath('data.status', 'completed');

        Sanctum::actingAs($this->owner, ['access', 'client']);
        $this->postJson("/api/v1/client/workspaces/{$this->workspace->id}/hiring-request-items/{$itemId}/decision", ['decision' => 'proceed'])
            ->assertOk()->assertJsonPath('data.status', 'awaiting_decisions');
        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-request-items/{$itemId}/decision", ['decision' => 'proceed'])
            ->assertOk()->assertJsonPath('data.status', 'ready_to_proceed');
    }

    public function test_two_expired_offers_disable_talent_without_closing_item(): void
    {
        $requestId = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$requestId}/submit"))->assertOk();
        $admin = $this->adminUser('expiry-admin@example.test');
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $item = $this->postJson("/api/v1/admin/hiring-requests/{$requestId}/start")->assertOk()->json('data.items.0');

        $first = HiringOffer::query()->where('request_item_id', $item['id'])->firstOrFail();
        $first->update(['response_due_at' => now()->subMinute()]);
        app(HiringOfferExpirationService::class)->expire($first->id);
        $second = $first->replicate(['status', 'sent_at', 'response_due_at']);
        $second->status = 'pending';
        $second->sent_at = now()->subHours(49);
        $second->response_due_at = now()->subMinute();
        $second->save();
        app(HiringOfferExpirationService::class)->expire($second->id);

        $this->assertFalse($this->talent->user->fresh()->is_active);
        $this->assertSame(2, $this->talent->fresh()->offer_no_response_count);
        $this->assertSame(HiringRequestItemStatusEnum::AWAITING_TALENT_RESPONSE, $this->talent->hiringRequestItems()->firstOrFail()->status);
    }

    public function test_talent_can_apologize_after_confirmation_and_admin_can_reschedule(): void
    {
        [$admin, $itemId, $interviewId] = $this->confirmedInterview();

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-interviews/{$interviewId}/apologize", [
            'reason' => 'I cannot attend the confirmed time.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'awaiting_reschedule')
            ->assertJsonPath('data.meeting_url', null);

        $this->assertDatabaseHas('hiring_request_items', [
            'id' => $itemId,
            'status' => HiringRequestItemStatusEnum::AWAITING_INTERVIEW_SLOTS->value,
        ]);

        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/schedule", [
            'starts_at' => now()->addDays(2)->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('data.id', $interviewId)
            ->assertJsonPath('data.status', 'awaiting_responses')
            ->assertJsonPath('data.talent_response.status', 'pending')
            ->assertJsonPath('data.client_response.status', 'pending');

        $this->assertDatabaseHas('hiring_request_events', [
            'request_item_id' => $itemId,
            'action' => 'interview_rescheduled',
        ]);
    }

    public function test_talent_offer_list_supports_design_filters_and_options(): void
    {
        $requestId = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$requestId}/submit"))->assertOk();
        $admin = $this->adminUser('filters-admin@example.test');
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $this->postJson("/api/v1/admin/hiring-requests/{$requestId}/start")->assertOk();

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->getJson('/api/v1/talent/hiring-offers/options')
            ->assertOk()
            ->assertJsonFragment(['value' => 'awaiting_talent_response'])
            ->assertJsonFragment(['value' => 'pending']);

        $this->getJson('/api/v1/talent/hiring-offers?status[]=awaiting_talent_response&offer_status[]=pending&search=Hiring')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.company.name', 'Hiring Workspace');
    }

    public function test_hybrid_remote_days_must_be_inside_selected_range(): void
    {
        $body = $this->draftBody();
        $body['items'][0]['offer']['work_mode'] = 'hybrid';
        $body['items'][0]['offer']['hybrid_work_days'] = ['saturday'];

        $this->postJson($this->clientUrl(), $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.offer.hybrid_work_days');
    }

    public function test_assign_reassign_and_responsible_admin_rules_are_enforced(): void
    {
        $id = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$id}/submit"))->assertOk();

        $first = $this->adminUser('first-hiring-admin@example.test');
        $second = $this->adminUser('second-hiring-admin@example.test');
        Sanctum::actingAs($first->user, ['access', 'admin']);

        $this->postJson("/api/v1/admin/hiring-requests/{$id}/assign", ['admin_id' => $first->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_admin.id', $first->id);
        $this->postJson("/api/v1/admin/hiring-requests/{$id}/reassign", ['admin_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_admin.id', $second->id);
        $this->postJson("/api/v1/admin/hiring-requests/{$id}/start")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hiring_request');

        Sanctum::actingAs($second->user, ['access', 'admin']);
        $this->postJson("/api/v1/admin/hiring-requests/{$id}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_request_from_another_workspace_is_not_exposed(): void
    {
        $id = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $outsider = $this->clientUser('hiring-outsider@example.test');
        Sanctum::actingAs($outsider, ['access', 'client']);

        $this->getJson($this->clientUrl("/{$id}"))->assertNotFound();
    }

    private function draftBody(): array
    {
        return ['items' => [[
            'talent_id' => $this->talent->id,
            'contract_type' => 'managed',
            'interview_required' => true,
            'offer' => [
                'salary_amount' => 6500,
                'currency_id' => $this->currency->id,
                'work_type' => 'full_time',
                'work_mode' => 'remote',
                'work_start_day' => 'sunday',
                'work_end_day' => 'thursday',
                'work_hours' => 8,
            ],
        ]]];
    }

    /** @return array{Admin, int, int} */
    private function confirmedInterview(): array
    {
        $requestId = $this->postJson($this->clientUrl(), $this->draftBody())->json('data.id');
        $this->postJson($this->clientUrl("/{$requestId}/submit"))->assertOk();
        $admin = $this->adminUser('reschedule-admin@example.test');
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $itemId = $this->postJson("/api/v1/admin/hiring-requests/{$requestId}/start")
            ->assertOk()->json('data.items.0.id');

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-offers/{$itemId}/accept")->assertOk();
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $interviewId = $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/schedule", [
            'starts_at' => now()->addDay()->toIso8601String(),
        ])->assertOk()->json('data.id');

        Sanctum::actingAs($this->talent->user, ['access', 'talent']);
        $this->postJson("/api/v1/talent/hiring-interviews/{$interviewId}/accept-schedule")->assertOk();
        Sanctum::actingAs($this->owner, ['access', 'client']);
        $this->postJson("/api/v1/client/workspaces/{$this->workspace->id}/hiring-interviews/{$interviewId}/accept-schedule")->assertOk();
        Sanctum::actingAs($admin->user, ['access', 'admin']);
        $this->postJson("/api/v1/admin/hiring-requests/items/{$itemId}/interview/confirm", [
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ])->assertOk();

        return [$admin, $itemId, $interviewId];
    }

    private function selectTalent(): void
    {
        WorkspaceTalentSelection::query()->updateOrCreate([
            'workspace_id' => $this->workspace->id,
            'talent_id' => $this->talent->id,
        ], ['added_by_member_id' => $this->member->id]);
    }

    private function clientUser(string $email): User
    {
        $user = User::query()->create([
            'first_name' => 'Client', 'last_name' => 'Owner',
            'email' => $email, 'password' => 'Password123!',
            'type' => PortalTypeEnum::CLIENT, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
        Client::query()->create(['user_id' => $user->id]);

        return $user->load('client');
    }

    private function adminUser(string $email): Admin
    {
        $user = User::query()->create([
            'first_name' => 'Hiring', 'last_name' => 'Admin',
            'email' => $email, 'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN, 'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([
            Role::query()->where('name', 'admin')->where('portal', PortalTypeEnum::ADMIN->value)->firstOrFail(),
        ]);

        return Admin::query()->create(['user_id' => $user->id])->load('user');
    }

    private function clientUrl(string $suffix = ''): string
    {
        return "/api/v1/client/workspaces/{$this->workspace->id}/hiring-requests{$suffix}";
    }
}
