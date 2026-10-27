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
        $this->applyJobFormExamples($endpointData, $parameters);
        $this->applyWorkspaceExamples($endpointData, $parameters);

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

        if ((str_ends_with($uri, '/schedule-interview') || str_ends_with($uri, '/interview/slots'))
            && isset($parameters['slots'])) {
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

    private function applyJobFormExamples(ExtractedEndpointData $endpointData, ?array &$parameters): void
    {
        if ($parameters === null || ! str_contains($endpointData->route->uri(), '/job')) {
            return;
        }

        $uri = $endpointData->route->uri();
        $examples = str_contains($uri, '/admin/job-forms') ? [
            'professional_field_id' => 1,
            'job_title_id' => 1,
            'summary' => 'Build reliable backend services for Qader products.',
            'about_role' => 'You will own APIs and collaborate with product and frontend teams.',
            'work_type' => 'full_time',
            'work_mode' => 'hybrid',
            'publishing_status' => 'published',
            'sections' => [
                ['section' => 'responsibility', 'items' => ['Design and maintain APIs.', 'Review backend changes.']],
                ['section' => 'requirement', 'items' => ['Strong PHP and Laravel experience.']],
                ['section' => 'qualification', 'items' => ['Good written and verbal communication.']],
            ],
            'professional_form_field_ids' => [12, 23, 38],
            'is_active' => true,
        ] : [
            'applicant_name' => 'Ahmed Hassan',
            'email' => 'ahmed.hassan@example.com',
            'phone_country_code' => '+20',
            'phone_number' => '1012345678',
            'consent' => true,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440000',
            'answers' => [
                'years_of_experience' => '3_to_5',
                'notice_period_days' => '30 days',
                'skill_ids' => [1, 2],
                'industry_ids' => [1, 2],
                'expected_salary' => ['amount' => 25000, 'currency_id' => 1],
            ],
        ];

        foreach ($examples as $name => $example) {
            if (isset($parameters[$name])) {
                $parameters[$name]['example'] = $example;
            }
        }
    }

    private function applyWorkspaceExamples(ExtractedEndpointData $endpointData, ?array &$parameters): void
    {
        if ($parameters === null || ! str_contains($endpointData->route->uri(), '/client/')) {
            return;
        }

        $uri = $endpointData->route->uri();
        $examples = match (true) {
            preg_match('~/client/workspaces(?:/\{workspace\})?$~', $uri) === 1 => [
                'name' => 'Qader Technology',
                'industry_ids' => [1, 2],
                'professional_field_ids' => [1, 2],
                'description' => 'Technology company building digital products and hiring specialized teams.',
                'is_company' => true,
                'country_id' => 1,
                'city_id' => 1,
                'size_band' => '11-50',
                'website_url' => 'https://qader.example.com',
                'social_links' => [
                    ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/qader'],
                    ['platform' => 'x', 'url' => 'https://x.com/qader'],
                ],
            ],
            str_contains($uri, '/access-control/roles') => [
                'name_en' => 'Hiring Manager',
                'name_ar' => 'مدير التوظيف',
                'is_active' => true,
                'permissions' => ['client.workspace.view', 'client.dashboard.view', 'client.members.view'],
            ],
            str_ends_with($uri, '/members') => [
                'first_name' => 'Team',
                'last_name' => 'Member',
                'email' => 'team.member@example.com',
                'role_ids' => [2, 3],
            ],
            str_contains($uri, '/members/') => [
                'status' => 'active',
                'role_ids' => [2, 3],
            ],
            default => [],
        };

        foreach ($examples as $name => $example) {
            if (isset($parameters[$name])) {
                $parameters[$name]['example'] = $example;
            }
        }
    }
}
