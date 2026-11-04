<?php

namespace Tests\Unit;

use App\Modules\V1\Talents\Domain\Exceptions\ResumeParserUnavailableException;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use App\Modules\V1\Talents\Infrastructure\ResumeParsing\ApiLayerResumeParserGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiLayerResumeParserGatewayTest extends TestCase
{
    private string $resumePath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.apilayer.resume_parser', [
            'key' => 'test-api-key',
            'base_url' => 'https://api.apilayer.test/resume_parser',
            'timeout' => 10,
        ]);

        $this->resumePath = tempnam(sys_get_temp_dir(), 'resume-');
        file_put_contents($this->resumePath, 'fake resume content');
    }

    protected function tearDown(): void
    {
        if (is_file($this->resumePath)) {
            unlink($this->resumePath);
        }

        parent::tearDown();
    }

    public function test_it_uploads_resume_and_normalizes_apilayer_response(): void
    {
        Http::fake([
            'https://api.apilayer.test/resume_parser/upload' => Http::response([
                'name' => 'Ahmed Wael',
                'email' => 'ahmed@example.com',
                'skills' => ['Laravel'],
                'experience' => [[
                    'title' => 'Backend Developer',
                    'organization' => 'Qader',
                ]],
            ]),
        ]);

        $result = app(ApiLayerResumeParserGateway::class)->parse($this->resumePath);

        $this->assertSame('Ahmed Wael', $result['candidate_name']);
        $this->assertSame('ahmed@example.com', $result['candidate_email']);
        $this->assertSame(['Laravel'], $result['skills']);
        $this->assertSame('Backend Developer', $result['positions'][0]['position_name']);
        $this->assertSame('Qader', $result['positions'][0]['company_name']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.apilayer.test/resume_parser/upload'
            && $request->hasHeader('apikey', 'test-api-key')
            && $request->hasHeader('Content-Type', 'application/octet-stream')
            && $request->body() === 'fake resume content');
    }

    public function test_it_rejects_invalid_resume_response(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid resume.'], 400),
        ]);

        $this->expectException(ResumeParsingFailedException::class);

        app(ApiLayerResumeParserGateway::class)->parse($this->resumePath);
    }

    public function test_it_reports_provider_limit_as_unavailable(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Monthly limit exceeded.'], 429),
        ]);

        $this->expectException(ResumeParserUnavailableException::class);

        app(ApiLayerResumeParserGateway::class)->parse($this->resumePath);
    }

    public function test_it_requires_an_api_key(): void
    {
        config()->set('services.apilayer.resume_parser.key');

        $this->expectException(ResumeParserUnavailableException::class);

        app(ApiLayerResumeParserGateway::class)->parse($this->resumePath);
    }
}
