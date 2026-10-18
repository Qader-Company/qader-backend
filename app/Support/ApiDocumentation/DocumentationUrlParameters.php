<?php

namespace App\Support\ApiDocumentation;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromLaravelAPI;

class DocumentationUrlParameters extends GetFromLaravelAPI
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $parameters = parent::__invoke($endpointData, $routeRules);

        foreach ($parameters ?? [] as $name => $parameter) {
            $parameters[$name]['example'] = DocumentationExamples::for(
                $name,
                $endpointData->route->uri(),
                $parameter['type'] ?? null,
            );
        }

        return $parameters;
    }
}
