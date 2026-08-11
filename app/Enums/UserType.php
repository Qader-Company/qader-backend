<?php

namespace App\Enums;

enum UserType: string
{
    case Talent = 'talent';
    case Client = 'client';
    case Admin = 'admin';
}
