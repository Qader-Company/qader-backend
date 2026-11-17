<?php

namespace Tests\Feature;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\JobForms\Domain\Models\JobFormSubmission;
use App\Modules\V1\JobForms\Domain\Models\JobFormVersion;
use App\Modules\V1\JobForms\Domain\Models\JobPosting;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\PlatformSettings\Application\Services\PlatformSettingsService;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Trash\Application\Services\TrashService;
use App\Modules\V1\Trash\Domain\Exceptions\TrashDependenciesException;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Settings\HiringSettings;
use App\Settings\TrashSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrashAndPlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_skills_and_tools_are_soft_deleted_without_removing_relationships(): void
    {
        $user = User::query()->create([
            'first_name' => 'Linked',
            'last_name' => 'Talent',
            'email' => 'linked-talent@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
        ]);
        $talent = $user->talent()->create();
        $field = ProfessionalField::query()->create(['is_active' => true]);
        $skill = Skill::query()->create(['name' => 'Laravel', 'is_active' => true]);
        $tool = Tool::query()->create(['name' => 'Docker', 'is_active' => true]);

        $talent->skills()->attach($skill);
        $talent->tools()->attach($tool);
        $field->skills()->attach($skill);
        $field->tools()->attach($tool);

        $service = app(TrashService::class);
        $service->softDelete('skills', $skill->id);
        $service->softDelete('tools', $tool->id);

        $this->assertSoftDeleted('skills', ['id' => $skill->id]);
        $this->assertSoftDeleted('tools', ['id' => $tool->id]);
        $this->assertDatabaseHas('talent_skills', ['talent_id' => $talent->id, 'skill_id' => $skill->id]);
        $this->assertDatabaseHas('talent_tools', ['talent_id' => $talent->id, 'tool_id' => $tool->id]);
        $this->assertDatabaseHas('professional_field_skill', ['professional_field_id' => $field->id, 'skill_id' => $skill->id]);
        $this->assertDatabaseHas('professional_field_tool', ['professional_field_id' => $field->id, 'tool_id' => $tool->id]);
    }

    public function test_dependency_conflict_response_lists_the_blocking_relations(): void
    {
        Route::get('/api/test-trash-dependencies', function (): never {
            throw new TrashDependenciesException(
                __('trash.has_dependencies'),
                ['workspaces' => 1, 'workspace_members' => 2],
            );
        });

        $this->getJson('/api/test-trash-dependencies')
            ->assertConflict()
            ->assertJson([
                'success' => false,
                'message' => __('trash.has_dependencies'),
                'errors' => [
                    'dependencies' => [
                        'workspaces' => 1,
                        'workspace_members' => 2,
                    ],
                ],
            ]);
    }

    public function test_profile_relations_do_not_block_talent_soft_deletion(): void
    {
        $user = User::query()->create([
            'first_name' => 'Blocked',
            'last_name' => 'Talent',
            'email' => 'blocked-talent@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
        ]);
        $talent = $user->talent()->create();
        $firstSkill = Skill::query()->create(['name' => 'PHP', 'is_active' => true]);
        $secondSkill = Skill::query()->create(['name' => 'Laravel', 'is_active' => true]);
        $talent->skills()->attach([$firstSkill->id, $secondSkill->id]);

        app(TrashService::class)->softDelete('talents', $talent->id);

        $this->assertSoftDeleted('talents', ['id' => $talent->id]);
        $this->assertDatabaseCount('talent_skills', 2);
    }

    public function test_operational_relations_still_block_talent_soft_deletion(): void
    {
        $user = User::query()->create([
            'first_name' => 'Operational',
            'last_name' => 'Talent',
            'email' => 'operational-talent@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
        ]);
        $talent = $user->talent()->create();
        TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'submitted_at' => now(),
        ]);

        try {
            app(TrashService::class)->softDelete('talents', $talent->id);
            $this->fail('Deleting a talent with operational dependencies should fail.');
        } catch (TrashDependenciesException $exception) {
            $this->assertSame(['talent_applications' => 1], $exception->dependencies);
            $this->assertSame(__('trash.has_dependencies'), $exception->getMessage());
        }

        $this->assertNotSoftDeleted('talents', ['id' => $talent->id]);
    }

    public function test_job_form_with_submissions_can_be_soft_deleted_without_losing_history(): void
    {
        $user = User::query()->create([
            'first_name' => 'Job',
            'last_name' => 'Admin',
            'email' => 'job-admin@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
        ]);
        $admin = Admin::query()->create(['user_id' => $user->id]);
        $field = ProfessionalField::query()->create(['is_active' => true]);
        $jobTitle = JobTitle::query()->create(['name' => 'QA Engineer', 'is_active' => true]);
        $posting = JobPosting::query()->create([
            'professional_field_id' => $field->id,
            'job_title_id' => $jobTitle->id,
            'title' => 'QA Engineer',
            'slug' => 'qa-engineer-test',
            'summary' => 'Summary',
            'about_role' => 'About role',
            'work_type' => 'full_time',
            'work_mode' => 'remote',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
            'created_by_admin_id' => $admin->id,
        ]);
        $version = JobFormVersion::query()->create([
            'job_posting_id' => $posting->id,
            'version_number' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $submission = JobFormSubmission::query()->create([
            'form_version_id' => $version->id,
            'applicant_name' => 'Applicant',
            'email' => 'applicant@example.test',
            'phone_country_code' => '+20',
            'phone_number' => '1000000000',
            'idempotency_key' => (string) Str::uuid(),
            'consent_at' => now(),
            'submitted_at' => now(),
        ]);

        app(TrashService::class)->softDelete('job-forms', $posting->id);

        $this->assertSoftDeleted('job_postings', ['id' => $posting->id]);
        $this->assertDatabaseHas('job_form_versions', ['id' => $version->id]);
        $this->assertDatabaseHas('job_form_submissions', ['id' => $submission->id]);
    }

    public function test_record_is_restored_then_removed_from_trash_without_database_deletion(): void
    {
        $service = app(TrashService::class);
        $skill = Skill::query()->create(['name' => 'Laravel', 'is_active' => true]);

        $service->softDelete('skills', $skill->id);

        $this->assertSoftDeleted('skills', ['id' => $skill->id]);
        $this->assertSame($skill->id, $service->paginate('skills')['items']->first()['id']);

        $service->restore('skills', $skill->id);

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'deleted_at' => null,
            'removed_from_trash_at' => null,
        ]);

        $service->softDelete('skills', $skill->id);
        $service->remove('skills', $skill->id);

        $stored = Skill::withTrashed()->findOrFail($skill->id);
        $this->assertNotNull($stored->deleted_at);
        $this->assertNotNull($stored->removed_from_trash_at);
        $this->assertSame(0, $service->paginate('skills')['meta']['total']);
    }

    public function test_retention_setting_controls_automatic_removal_dynamically(): void
    {
        $settings = app(TrashSettings::class);
        $settings->retention_days = 3;
        $settings->save();

        $service = app(TrashService::class);
        $skill = Skill::query()->create(['name' => 'PHP', 'is_active' => true]);
        $service->softDelete('skills', $skill->id);
        Skill::withTrashed()->whereKey($skill->id)->update(['deleted_at' => now()->subDays(4)]);

        $this->assertGreaterThanOrEqual(1, $service->removeExpired());
        $this->assertNotNull(Skill::withTrashed()->findOrFail($skill->id)->removed_from_trash_at);
    }

    public function test_account_restore_preserves_previous_activation_and_removal_releases_email(): void
    {
        $user = User::query()->create([
            'first_name' => 'Empty',
            'last_name' => 'Client',
            'email' => 'reusable@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::CLIENT,
            'is_active' => true,
        ]);
        $client = Client::query()->create(['user_id' => $user->id]);
        $service = app(TrashService::class);

        $service->softDelete('clients', $client->id);
        $this->assertFalse($user->refresh()->is_active);

        $service->restore('clients', $client->id);
        $this->assertTrue($user->refresh()->is_active);

        $service->softDelete('clients', $client->id);
        $service->remove('clients', $client->id);

        $this->assertNotSame('reusable@example.test', $user->refresh()->email);
        User::query()->create([
            'first_name' => 'New',
            'last_name' => 'Client',
            'email' => 'reusable@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::CLIENT,
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_platform_setting_update_is_partial_and_audited(): void
    {
        $user = User::query()->create([
            'first_name' => 'Settings',
            'last_name' => 'Admin',
            'email' => 'settings-admin@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
        ]);
        $admin = Admin::query()->create(['user_id' => $user->id]);

        $updated = app(PlatformSettingsService::class)->update(
            'hiring',
            ['offer_response_duration_hours' => 72],
            $user,
        );

        $this->assertSame(72, $updated['offer_response_duration_hours']);
        $this->assertSame(24, $updated['post_interview_decision_duration_hours']);
        $this->assertSame(72, app(HiringSettings::class)->offer_response_duration_hours);
        $this->assertDatabaseHas('platform_setting_events', [
            'actor_admin_id' => $admin->id,
            'group' => 'hiring',
        ]);
    }
}
