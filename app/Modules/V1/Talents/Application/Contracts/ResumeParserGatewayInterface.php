<?php

namespace App\Modules\V1\Talents\Application\Contracts;

interface ResumeParserGatewayInterface
{
    /** @return array<string, mixed> */
    public function parse(string $filePath): array;
}
