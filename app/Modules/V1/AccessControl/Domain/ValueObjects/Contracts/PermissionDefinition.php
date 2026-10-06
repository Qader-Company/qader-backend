<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

interface PermissionDefinition
{
    public function portal(): PortalTypeEnum;

    public function section(): string;

    public function label(?string $locale = null): string;

    public function middleware(): string;

    public static function values(): array;
}
