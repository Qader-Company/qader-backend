<?php

namespace App\Modules\V1\Admins\Application\Contracts;

use Illuminate\Http\UploadedFile;

interface AdminAvatarStorageInterface
{
    public function store(UploadedFile $avatar): string;

    public function delete(?string $path): void;
}
