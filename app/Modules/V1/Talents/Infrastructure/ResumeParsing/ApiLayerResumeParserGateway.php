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
use Throwable;

class ApiLayerResumeParserGateway implements ResumeParserGatewayInterface
{
    public function parse(string $filePath): array
    {
        $apiKey = (string) config('services.apilayer.resume_parser.key');

        if (blank($apiKey)) {
            throw new ResumeParserUnavailableException(__('talents.resume_parser_not_configured'));
        }

        try {
            $response = Http::acceptJson()
                ->withHeader('apikey', $apiKey)
                ->withBody(File::get($filePath), 'application/octet-stream')
                ->timeout((int) config('services.apilayer.resume_parser.timeout', 60))
                ->post(rtrim((string) config('services.apilayer.resume_parser.base_url'), '/').'/upload');
        } catch (ConnectionException $exception) {
            $this->logFailure($exception);

            throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'), previous: $exception);
        } catch (Throwable $exception) {
            $this->logFailure($exception);

            throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'), previous: $exception);
        }

        $this->ensureSuccessful($response);

        $payload = $response->json();
        if (! is_array($payload) || $payload === []) {
            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
        }

        return $this->normalize($payload);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        Log::warning('Resume parser provider request failed.', [
            'provider' => 'apilayer',
            'status' => $response->status(),
        ]);

        if ($response->status() === 400 || $response->status() === 422) {
            throw new ResumeParsingFailedException(__('talents.resume_parse_failed'));
        }

        throw new ResumeParserUnavailableException(__('talents.resume_parser_unavailable'));
    }

    /** @return array<string, mixed> */
    private function normalize(array $payload): array
    {
        $experiences = Arr::get($payload, 'experience', []);

        return [
            'candidate_name' => $this->firstValue($payload, ['name', 'full_name']),
            'candidate_email' => $this->firstValue($payload, ['email', 'email_address']),
            'candidate_phone' => $this->firstValue($payload, ['phone', 'phone_number', 'mobile']),
            'candidate_address' => $this->firstValue($payload, ['address', 'location']),
            'candidate_summary_objective' => $this->firstValue($payload, ['summary', 'objective']),
            'candidate_linkedin' => $this->firstValue($payload, ['linkedin', 'linkedin_url']),
            'candidate_github' => $this->firstValue($payload, ['github', 'github_url']),
            'candidate_website' => $this->firstValue($payload, ['website', 'portfolio']),
            'skills' => $this->listValue($payload, ['skills']),
            'candidate_spoken_languages' => $this->listValue($payload, ['languages']),
            'positions' => collect(is_array($experiences) ? $experiences : [])
                ->filter(fn (mixed $experience): bool => is_array($experience))
                ->map(fn (array $experience): array => [
                    'position_name' => $this->firstValue($experience, ['title', 'position', 'job_title']),
                    'company_name' => $this->firstValue($experience, ['organization', 'company', 'company_name']),
                    'start_date' => $this->firstValue($experience, ['start_date']),
                    'end_date' => $this->firstValue($experience, ['end_date']),
                    'job_details' => $this->firstValue($experience, ['description', 'details']),
                    'skills' => $this->listValue($experience, ['skills']),
                ])
                ->values()
                ->all(),
        ];
    }

    private function firstValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /** @return array<int, mixed> */
    private function listValue(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);
            if (is_array($value)) {
                return array_values($value);
            }
        }

        return [];
    }

    private function logFailure(Throwable $exception): void
    {
        Log::warning('Resume parser provider is unavailable.', [
            'provider' => 'apilayer',
            'exception' => $exception::class,
            'code' => $exception->getCode(),
        ]);
    }
}
