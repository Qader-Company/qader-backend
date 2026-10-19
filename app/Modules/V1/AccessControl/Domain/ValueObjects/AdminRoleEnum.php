<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum AdminRoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';

    public function nameEn(): string
    {
        return 'Super Admin';
    }

    public function nameAr(): string
    {
        return 'مدير النظام';
    }
}
