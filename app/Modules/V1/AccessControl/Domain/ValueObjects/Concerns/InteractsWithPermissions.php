<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects\Concerns;

trait InteractsWithPermissions
{
    public function section(): string
    {
        return explode('.', $this->value, 3)[1];
    }

    public function label(?string $locale = null): string
    {
        return __("permissions.{$this->value}", locale: $locale);
    }

    public function middleware(): string
    {
        return "permission:{$this->value}";
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases()
        );
    }
}
