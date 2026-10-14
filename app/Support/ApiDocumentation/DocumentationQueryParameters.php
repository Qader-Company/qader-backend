<?php

namespace App\Support\ApiDocumentation;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromFormRequest;
use ReflectionClass;

class DocumentationQueryParameters extends GetFromFormRequest
{
    private bool $readRequest = false;

    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $this->readRequest = in_array($endpointData->httpMethods[0], ['GET', 'HEAD'], true);

        return parent::__invoke($endpointData, $routeRules);
    }

    protected function isFormRequestMeantForThisStrategy(ReflectionClass $formRequestReflectionClass): bool
    {
        return $this->readRequest || parent::isFormRequestMeantForThisStrategy($formRequestReflectionClass);
    }
}
