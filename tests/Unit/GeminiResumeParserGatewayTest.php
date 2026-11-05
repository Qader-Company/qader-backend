<?php

namespace Tests\Unit;

use App\Modules\V1\Talents\Domain\Exceptions\ResumeParserUnavailableException;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use App\Modules\V1\Talents\Infrastructure\ResumeParsing\GeminiResumeParserGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiResumeParserGatewayTest extends TestCase
{
    private string $resumePath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.gemini', [
            'api_key' => 'test-api-key',
            'resume_parser' => [
                'model' => 'gemini-test-flash',
                'base_url' => 'https://generativelanguage.test/v1beta',
                'timeout' => 10,
            ],
        ]);

        $this->resumePath = sys_get_temp_dir().'/resume-'.uniqid().'.pdf';
        file_put_contents($this->resumePath, 'fake resume content');
    }

    protected function tearDown(): void
    {
        if (is_file($this->resumePath)) {
            unlink($this->resumePath);
        }

        parent::tearDown();
    }

    public function test_it_sends_resume_with_json_schema_and_returns_structured_result(): void
    {
        $parsed = $this->parsedResume();

        Http::fake([
            'https://generativelanguage.test/v1beta/models/gemini-test-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => json_encode($parsed, JSON_THROW_ON_ERROR)]],
                    ],
                ]],
            ]),
        ]);

        $result = app(GeminiResumeParserGateway::class)->parse($this->resumePath);

        $this->assertSame('Ahmed Wael', $result['full_name']);
        $this->assertSame(['Laravel'], $result['skills']);
        $this->assertSame('Backend Developer', $result['positions'][0]['position_name']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://generativelanguage.test/v1beta/models/gemini-test-flash:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-api-key')
                && data_get($payload, 'generationConfig.responseMimeType') === 'application/json'
                && data_get($payload, 'generationConfig.responseJsonSchema.type') === 'object'
                && data_get($payload, 'generationConfig.responseSchema') === null
                && data_get($payload, 'contents.0.parts.1.inlineData.mimeType') === 'application/pdf'
                && base64_decode((string) data_get($payload, 'contents.0.parts.1.inlineData.data'), true) === 'fake resume content';
        });
    }

    public function test_it_rejects_a_response_without_structured_content(): void
    {
        Http::fake(['*' => Http::response(['candidates' => []])]);

        $this->expectException(ResumeParsingFailedException::class);

        app(GeminiResumeParserGateway::class)->parse($this->resumePath);
    }

    public function test_it_rejects_invalid_json_from_provider(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'not-json']]],
            ]],
        ])]);

        $this->expectException(ResumeParsingFailedException::class);

        app(GeminiResumeParserGateway::class)->parse($this->resumePath);
    }

    public function test_it_reports_provider_limit_as_unavailable(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Quota exceeded.']], 429)]);

        $this->expectException(ResumeParserUnavailableException::class);

        app(GeminiResumeParserGateway::class)->parse($this->resumePath);
    }

    public function test_it_requires_an_api_key(): void
    {
        config()->set('services.gemini.api_key');

        $this->expectException(ResumeParserUnavailableException::class);

        app(GeminiResumeParserGateway::class)->parse($this->resumePath);
    }

    /** @return array<string, mixed> */
    private function parsedResume(): array
    {
        return [
            'full_name' => 'Ahmed Wael',
            'email' => 'ahmed@example.com',
            'phone_country_code' => null,
            'phone_number' => null,
            'residence_country' => null,
            'residence_city' => null,
            'professional_summary' => null,
            'job_title' => 'Backend Developer',
            'linkedin_url' => null,
            'github_url' => null,
            'behance_url' => null,
            'portfolio_url' => null,
            'years_of_experience' => 5,
            'skills' => ['Laravel'],
            'tools' => ['Docker'],
            'languages' => ['Arabic'],
            'positions' => [[
                'position_name' => 'Backend Developer',
                'company_name' => 'Qader',
                'company_url' => null,
                'start_date' => null,
                'end_date' => null,
                'job_details' => null,
                'skills' => ['Laravel'],
            ]],
            'projects' => [],
        ];
    }
}
