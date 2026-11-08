<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Rules;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotSuperAdminRoleName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            is_string($value)
            && Str::slug(trim($value), '_') === AdminRoleEnum::SUPER_ADMIN->value
        ) {
            $fail(__('admins.super_admin_role_name_is_reserved'));
        }
    }
}
