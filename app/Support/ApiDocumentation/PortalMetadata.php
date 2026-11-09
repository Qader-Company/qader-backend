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
        $portal = $isPublicJob ? 'website' : ($isAuth ? ($isSharedAuth ? 'talent' : $segments[3]) : $segments[2]);
        $module = $isPublicJob ? 'jobs' : ($isAuth ? 'auth' : ($segments[3] ?? 'general'));
        if ($portal === 'client' && in_array($module, ['workspaces', 'access-control'], true)) {
            $module = match (true) {
                in_array('talent-marketplace', $segments, true) => 'talent-marketplace',
                in_array('talent-selections', $segments, true) => 'talent-selections',
                in_array('help-requests', $segments, true) => 'help-requests',
                in_array('hiring-requests', $segments, true) => 'hiring-requests',
                in_array('hiring-interviews', $segments, true) => 'hiring-interviews',
                in_array('hiring-request-items', $segments, true) => 'hiring-request-items',
                default => $module,
            };
        }

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
            $metadata['description'] = str_ends_with($endpointData->route->uri(), '/resume/parse')
                ? 'Uploads a temporary PDF, DOC, or DOCX resume, returns autocomplete suggestions, and deletes the temporary file. This endpoint does not save the CV or update the talent profile.'
                : 'Use GET /api/v1/talent/onboarding/form-fields for the input keys enabled for the selected professional field. PATCH profile saves a partial draft; POST onboarding step validates the required backend rules before Next or Submit.';
        }

        return $metadata;
    }
}
