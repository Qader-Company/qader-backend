<?php

namespace App\Support\ApiDocumentation;

use App\Modules\V1\Admins\Presentation\Http\Requests\UpdateAdminRequest;
use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Cities\Presentation\Http\Requests\UpdateCityRequest;
use App\Modules\V1\ProfessionalFields\Application\Factories\ProfessionalFieldFormValidationFactory;
use App\Modules\V1\Talents\Presentation\Http\Requests\SaveTalentOnboardingStepRequest;
use App\Modules\V1\Talents\Presentation\Http\Requests\UpdateTalentProfileRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromFormRequest;

class DocumentationBodyParameters extends GetFromFormRequest
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        if (in_array($endpointData->httpMethods[0], ['GET', 'HEAD'], true)) {
            return null;
        }

        $parameters = parent::__invoke($endpointData, $routeRules);

        foreach ($parameters ?? [] as $name => $parameter) {
            $type = $parameter['type'] ?? null;

            if (! DocumentationExamples::shouldKeepGeneratedBodyExample($name, $type)) {
                $parameters[$name]['example'] = DocumentationExamples::for(
                    $name,
                    $endpointData->route->uri(),
                    $type,
                );
            }

            if (str_ends_with($name, '_confirmation')) {
                $original = substr($name, 0, -strlen('_confirmation'));
                if (isset($parameters[$original]['example'])) {
                    $parameters[$name]['example'] = $parameters[$original]['example'];
                }
            }
        }

        $this->applyStableAuthExamples($endpointData, $parameters);
        $this->applyTalentApplicationExamples($endpointData, $parameters);

        return $parameters;
    }

    protected function getRouteValidationRules(FormRequest $formRequest)
    {
        if ($formRequest instanceof UpdateCityRequest) {
            $route = clone $formRequest->route();
            $route->setParameter('city', (new City)->forceFill(['id' => 1, 'country_id' => 1]));
            $formRequest->setRouteResolver(fn () => $route);
        }

        $dynamicTalentRequest = $formRequest instanceof UpdateTalentProfileRequest
            || $formRequest instanceof SaveTalentOnboardingStepRequest;

        if (! $formRequest instanceof UpdateAdminRequest && ! $dynamicTalentRequest) {
            return $this->withConfirmationFields(parent::getRouteValidationRules($formRequest));
        }

        // These rules look up the current user's records. Documentation describes
        // the generic request shape without needing a database or a logged-in user.
        $rules = [];
        DB::pretend(function () use ($formRequest, $dynamicTalentRequest, &$rules): void {
            $rules = $dynamicTalentRequest
                ? $formRequest->rules(new ProfessionalFieldFormValidationFactory(new DocumentationFormFieldPolicy))
                : parent::getRouteValidationRules($formRequest);
        });

        return $this->withConfirmationFields($rules);
    }

    private function withConfirmationFields(array $rules): array
    {
        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            if (in_array('confirmed', $fieldRules, true)) {
                $rules[$field.'_confirmation'] ??= ['required_with:'.$field, 'string', 'same:'.$field];
            }
        }

        return $rules;
    }

    private function applyStableAuthExamples(ExtractedEndpointData $endpointData, ?array &$parameters): void
    {
        if ($parameters === null || ! str_contains($endpointData->route->uri(), 'api/v1/auth/')) {
            return;
        }

        $uri = $endpointData->route->uri();
        preg_match('~api/v1/auth/(talent|client|admin)/~', $uri, $matches);
        $portal = $matches[1] ?? null;

        $examples = match (true) {
            str_ends_with($uri, '/register') => [
                'first_name' => 'New',
                'last_name' => ucfirst((string) $portal),
                'email' => 'new.'.$portal.'@qader.test',
                'password' => config('demo_accounts.password'),
                'password_confirmation' => config('demo_accounts.password'),
            ],
            str_ends_with($uri, '/login') && ! str_contains($uri, '/social/') => [
                'email' => config('demo_accounts.accounts.'.$portal.'.email'),
                'password' => config('demo_accounts.password'),
                'fcm_token' => 'demo-web-fcm-token',
                'platform' => 'web',
            ],
            str_contains($uri, '/social/') => [
                'token' => '{GOOGLE_ID_TOKEN}',
                'name' => 'Demo '.ucfirst((string) $portal),
                'fcm_token' => 'demo-web-fcm-token',
                'platform' => 'web',
            ],
            str_ends_with($uri, '/send-otp') => [
                'email' => config('demo_accounts.accounts.'.$portal.'.email'),
            ],
            str_ends_with($uri, '/email-verification') => [
                'otp' => '123456',
            ],
            str_ends_with($uri, '/reset-password-verification') => [
                'email' => config('demo_accounts.accounts.'.$portal.'.email'),
                'otp' => '123456',
            ],
            str_ends_with($uri, '/reset-password') => [
                'password' => config('demo_accounts.password'),
                'password_confirmation' => config('demo_accounts.password'),
            ],
            default => [],
        };

        foreach ($examples as $name => $example) {
            if (isset($parameters[$name])) {
                $parameters[$name]['example'] = $example;
            }
        }
    }

    private function applyTalentApplicationExamples(ExtractedEndpointData $endpointData, ?array &$parameters): void
    {
        if ($parameters === null || ! str_contains($endpointData->route->uri(), '/admin/talent-applications')) {
            return;
        }

        $uri = $endpointData->route->uri();

        if (str_ends_with($uri, '/evaluation') && isset($parameters['tips'])) {
            $parameters['tips']['example'] = [[
                'title' => 'Improve system design',
                'priority' => 'high',
                'description' => 'Practice explaining architecture trade-offs.',
            ]];
        }

        if (str_ends_with($uri, '/schedule-interview') && isset($parameters['slots'])) {
            $parameters['slots']['example'] = [
                [
                    'mode' => 'online',
                    'start' => '2026-11-01T12:00:00+02:00',
                ],
                [
                    'mode' => 'offline',
                    'start' => '2026-11-02T15:00:00+02:00',
                    'location' => 'Qader Office, Giza',
                    'latitude' => 30.0444200,
                    'longitude' => 31.2357120,
                ],
            ];
        }
    }
}
