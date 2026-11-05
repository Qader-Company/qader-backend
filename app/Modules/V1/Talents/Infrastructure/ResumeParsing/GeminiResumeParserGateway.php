<?php

namespace App\Modules\V1\Talents\Infrastructure\ResumeParsing;

use App\Modules\V1\Talents\Application\Contracts\ResumeParserGatewayInterface;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParserUnavailableException;
use App\Modules\V1\Talents\Domain\Exceptions\ResumeParsingFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class GeminiResumeParserGateway implements ResumeParserGatewayInterface
{
    public function parse(string $filePath): array
    {
        $apiKey = (string) config('services.gemini.api_key');

        if (blank($apiKey)) {
            throw new ResumeParserUnavailableException(__('talents.resume_parser_not_configured'));
        }

        try {
            $response = Http::acceptJson()
                ->withHeader('x-goog-api-key', $apiKey)
                ->timeout((int) config('services.gemini.resume_parser.timeout', 90))
                ->post($this->endpoint(), $this->payload($filePath));
        } catch (ConnectionException $exception) {
            $this->logFailure($exception);

            throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'), previous: $exception);
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'), previous: $exception);
        }

        $this->ensureSuccessful($response);

        $content = Arr::get($response->json(), 'candidates.0.content.parts.0.text');
        if (! is_string($content) || trim($content) === '') {
            Log::warning('Resume parser provider returned no structured content.', [
                'provider' => 'gemini',
                'finish_reason' => Arr::get($response->json(), 'candidates.0.finishReason'),
                'block_reason' => Arr::get($response->json(), 'promptFeedback.blockReason'),
            ]);

            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
        }

        try {
            $parsed = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'), previous: $exception);
        }

        if (! is_array($parsed) || $parsed === []) {
            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
        }

        return $parsed;
    }

    private function endpoint(): string
    {
        $baseUrl = rtrim((string) config('services.gemini.resume_parser.base_url'), '/');
        $model = rawurlencode((string) config('services.gemini.resume_parser.model', 'gemini-3.8-flash'));

        return "{$baseUrl}/models/{$model}:generateContent";
    }

    /** @return array<string, mixed> */
    private function payload(string $filePath): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => $this->prompt()],
                    ['inlineData' => [
                        'mimeType' => $this->mimeType($filePath),
                        'data' => base64_encode(File::get($filePath)),
                    ]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->schema(),
            ],
        ];
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
Extract only information explicitly present in this resume. Preserve the original language of names and text. Split the phone into phone_country_code and phone_number when the country code is explicitly present. Do not infer, translate, score, or invent missing values. Return null for unknown scalar values and empty arrays for unknown lists. Dates must use YYYY-MM-DD when the full date is known; otherwise return null. Return only the JSON object required by the response schema.
PROMPT;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];
        $stringList = ['type' => 'array', 'items' => ['type' => 'string']];

        return [
            'type' => 'object',
            'properties' => [
                'full_name' => $nullableString,
                'email' => $nullableString,
                'phone_country_code' => $nullableString,
                'phone_number' => $nullableString,
                'residence_country' => $nullableString,
                'residence_city' => $nullableString,
                'professional_summary' => $nullableString,
                'job_title' => $nullableString,
                'linkedin_url' => $nullableString,
                'github_url' => $nullableString,
                'behance_url' => $nullableString,
                'portfolio_url' => $nullableString,
                'years_of_experience' => ['type' => ['integer', 'null']],
                'skills' => $stringList,
                'tools' => $stringList,
                'languages' => $stringList,
                'positions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'position_name' => $nullableString,
                            'company_name' => $nullableString,
                            'company_url' => $nullableString,
                            'start_date' => $nullableString,
                            'end_date' => $nullableString,
                            'job_details' => $nullableString,
                            'skills' => $stringList,
                        ],
                        'required' => [
                            'position_name', 'company_name', 'company_url', 'start_date',
                            'end_date', 'job_details', 'skills',
                        ],
                    ],
                ],
                'projects' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'project_name' => $nullableString,
                            'description' => $nullableString,
                            'url' => $nullableString,
                        ],
                        'required' => ['project_name', 'description', 'url'],
                    ],
                ],
            ],
            'required' => [
                'full_name', 'email', 'phone_country_code', 'phone_number',
                'residence_country', 'residence_city', 'professional_summary',
                'job_title', 'linkedin_url', 'github_url', 'behance_url',
                'portfolio_url', 'years_of_experience', 'skills', 'tools',
                'languages', 'positions', 'projects',
            ],
        ];
    }

    private function mimeType(string $filePath): string
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => File::mimeType($filePath) ?: 'application/octet-stream',
        };
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        Log::warning('Resume parser provider request failed.', [
            'provider' => 'gemini',
            'status' => $response->status(),
            'provider_status' => Arr::get($response->json(), 'error.status'),
            'provider_message' => Arr::get($response->json(), 'error.message'),
        ]);

        if (in_array($response->status(), [400, 422], true)) {
            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
        }

        throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'));
    }

    private function logFailure(Throwable $exception): void
    {
        Log::warning('Resume parser provider is unavailable.', [
            'provider' => 'gemini',
            'exception' => $exception::class,
            'code' => $exception->getCode(),
        ]);
    }
}
