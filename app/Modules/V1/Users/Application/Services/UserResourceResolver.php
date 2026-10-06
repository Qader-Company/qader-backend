<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Presentation\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceResolver
{
    public static function resolve(User $user): JsonResource
    {
        return new UserResource($user);
    }
}
