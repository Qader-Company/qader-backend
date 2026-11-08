<?php

namespace App\Support\ApiDocumentation;

use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

class ApidogFolders extends OpenApiGenerator
{
    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $pathItem['x-apidog-folder'] = $endpoint->metadata->groupName.'/'.$endpoint->metadata->subgroup;
        $pathItem['x-apidog-name'] = $endpoint->metadata->title;

        if ($endpoint->cleanBodyParameters) {
            foreach (array_keys($pathItem['requestBody']['content'] ?? []) as $contentType) {
                $pathItem['requestBody']['content'][$contentType]['example'] = $endpoint->cleanBodyParameters;
            }
        }

        // OpenAPI requires a response map. Response calls are disabled locally,
        // so use a descriptive fallback rather than an invalid empty array.
        if (empty($pathItem['responses'])) {
            $pathItem['responses'] = [
                'default' => ['description' => 'Response depends on the operation outcome. Response examples have not been documented yet.'],
            ];
        }

        return $pathItem;
    }
}
