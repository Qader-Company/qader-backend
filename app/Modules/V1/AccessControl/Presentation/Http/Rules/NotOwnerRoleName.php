<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotOwnerRoleName implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            is_string($value)
            && strcasecmp(trim($value), 'owner') === 0
        ) {
            $fail(__('admins.owner_role_is_reserved'));
        }
    }
}
