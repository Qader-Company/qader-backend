<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Application\Contracts\ResumeParserGatewayInterface;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParserUnavailableException;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Tools\Domain\Models\Tool;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TalentResumeParserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Talent $talent;

    private FakeResumeParserGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        $this->gateway = new FakeResumeParserGateway;
        $this->app->instance(ResumeParserGatewayInterface::class, $this->gateway);

        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $city = City::query()->create([
            'country_id' => $country->id,
            'is_active' => true,
            'en' => ['name' => 'Cairo'],
            'ar' => ['name' => 'القاهرة'],
        ]);
        $field = ProfessionalField::query()->create([
            'is_active' => true,
            'en' => ['name' => 'Software'],
            'ar' => ['name' => 'برمجيات'],
        ]);
        $jobTitle = JobTitle::query()->create([
            'name' => 'Backend Developer',
            'is_active' => true,
        ]);
        $jobTitle->professionalFields()->attach($field);
        $skill = Skill::query()->create(['name' => 'Laravel', 'is_active' => true]);
        $skill->professionalFields()->attach($field);
        $tool = Tool::query()->create(['name' => 'Docker', 'is_active' => true]);
        $tool->professionalFields()->attach($field);
        Language::query()->create([
            'code' => 'en',
            'is_active' => true,
            'en' => ['name' => 'English'],
            'ar' => ['name' => 'الإنجليزية'],
        ]);

        $this->user = User::query()->create([
            'first_name' => 'Resume',
            'last_name' => 'Talent',
            'email' => 'resume-talent@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->talent = Talent::query()->create([
            'user_id' => $this->user->id,
            'professional_field_id' => $field->id,
            'residence_country_id' => $country->id,
            'residence_city_id' => $city->id,
        ]);

        Sanctum::actingAs($this->user, ['access', 'talent']);
    }

    public function test_it_parses_resume_into_qader_autocomplete_suggestions_without_storing_file(): void
    {
        $this->gateway->result = [
            'full_name' => 'Ahmed Wael',
            'email' => 'ahmed@example.com',
            'phone_country_code' => '+20',
            'phone_number' => '1000000000',
            'residence_country' => 'Egypt',
            'residence_city' => 'Cairo',
            'professional_summary' => 'Backend developer building reliable APIs.',
            'linkedin_url' => 'https://linkedin.com/in/ahmed',
            'github_url' => 'https://github.com/ahmed',
            'years_of_experience' => 5,
            'languages' => ['English'],
            'tools' => ['Docker'],
            'positions' => [[
                'position_name' => 'Backend Developer',
                'company_name' => 'Example Company',
                'start_date' => '2022-01-01',
                'end_date' => null,
                'job_details' => 'Built Laravel APIs.',
                'skills' => ['Laravel', 'Event Sourcing'],
            ]],
        ];

        $response = $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('ahmed-cv.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json', 'Accept-Language' => 'en']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cv.file_name', 'ahmed-cv.pdf')
            ->assertJsonPath('data.suggestions.personal_information.full_name', 'Ahmed Wael')
            ->assertJsonPath('data.suggestions.personal_information.email', 'ahmed@example.com')
            ->assertJsonPath('data.suggestions.personal_information.phone_country_code', '+20')
            ->assertJsonPath('data.suggestions.personal_information.residence_country_id', 1)
            ->assertJsonPath('data.suggestions.personal_information.residence_city_id', 1)
            ->assertJsonPath('data.suggestions.personal_information.languages.0.language_id', 1)
            ->assertJsonPath('data.suggestions.skills_and_experiences.job_title_id', 1)
            ->assertJsonPath('data.suggestions.skills_and_experiences.skill_ids.0', 1)
            ->assertJsonPath('data.suggestions.skills_and_experiences.tool_ids.0', 1)
            ->assertJsonPath('data.suggestions.skills_and_experiences.experiences.0.currently_working', true)
            ->assertJsonMissingPath('data.unmatched')
            ->assertJsonMissingPath('data.warnings');

        $this->assertNotNull($this->gateway->receivedPath);
        $this->assertFileDoesNotExist($this->gateway->receivedPath);
        $this->assertCount(0, $this->talent->getMedia('cv'));
        $this->assertSame('Resume', $this->user->fresh()->first_name);
    }

    public function test_it_rejects_unsupported_resume_file(): void
    {
        $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('resume.txt', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cv');

        $this->assertNull($this->gateway->receivedPath);
    }

    public function test_it_returns_validation_error_when_resume_cannot_be_parsed(): void
    {
        $this->gateway->exception = new ResumeParsingFailedException('Unable to parse resume.');

        $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cv');

        $this->assertNotNull($this->gateway->receivedPath);
        $this->assertFileDoesNotExist($this->gateway->receivedPath);
    }

    public function test_it_returns_service_unavailable_when_provider_is_down(): void
    {
        $this->gateway->unavailableException = new ResumeParserUnavailableException('Resume parser unavailable.');

        $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('resume.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resume parser unavailable.');

        $this->assertNotNull($this->gateway->receivedPath);
        $this->assertFileDoesNotExist($this->gateway->receivedPath);
    }

    public function test_client_token_cannot_use_talent_resume_parser(): void
    {
        Sanctum::actingAs($this->user, ['access', 'client']);

        $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_resume_parser_is_rate_limited_per_talent(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post('/api/v1/talent/onboarding/resume/parse', [
                'cv' => UploadedFile::fake()->create("resume-{$attempt}.pdf", 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->post('/api/v1/talent/onboarding/resume/parse', [
            'cv' => UploadedFile::fake()->create('resume-4.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertTooManyRequests();
    }
}

class FakeResumeParserGateway implements ResumeParserGatewayInterface
{
    public array $result = ['candidate_name' => 'Resume Talent'];

    public ?ResumeParsingFailedException $exception = null;

    public ?ResumeParserUnavailableException $unavailableException = null;

    public ?string $receivedPath = null;

    public function parse(string $filePath): array
    {
        $this->receivedPath = $filePath;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->unavailableException !== null) {
            throw $this->unavailableException;
        }

        return $this->result;
    }
}
