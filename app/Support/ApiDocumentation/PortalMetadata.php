<?php

namespace App\Support\ApiDocumentation;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

class PortalMetadata extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $segments = explode('/', $endpointData->route->uri());
        $isAuth = ($segments[2] ?? null) === 'auth';
        $isSharedAuth = in_array(
            $endpointData->route->uri(),
            ['api/v1/auth/logout', 'api/v1/auth/me'],
            true
        );
        $isPublicJob = ($segments[2] ?? null) === 'public' && ($segments[3] ?? null) === 'jobs';
        $portal = $isPublicJob ? 'marketing' : ($isAuth ? ($isSharedAuth ? 'talent' : $segments[3]) : $segments[2]);
        $module = $isPublicJob ? 'jobs' : ($isAuth ? 'auth' : ($segments[3] ?? 'general'));

        if (! in_array($portal, PortalRouteMatcher::PORTALS, true)) {
            throw new \LogicException('Assign this API route to a documentation portal: '.$endpointData->route->uri());
        }

        $metadata = [
            'title' => $endpointData->metadata->title ?: EndpointNames::for($endpointData->route),
            'groupName' => $portal,
            'subgroup' => $module,
            'authenticated' => in_array('auth:sanctum', $endpointData->route->gatherMiddleware(), true),
        ];

        if ($isSharedAuth) {
            $metadata['description'] = 'Shared authentication endpoint for talent, client, and admin. Use the token for the relevant portal.';
        }

        if ($portal === 'talent' && in_array($module, ['profile', 'onboarding'], true)
            && in_array($endpointData->httpMethods[0], ['POST', 'PATCH'], true)) {
            $metadata['description'] = 'Use GET /api/v1/talent/onboarding/form-fields for the input keys enabled for the selected professional field. PATCH profile saves a partial draft; POST onboarding step validates the required backend rules before Next or Submit.';
        }

        return $metadata;
    }
}
