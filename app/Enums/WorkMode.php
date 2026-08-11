<?php

namespace App\Enums;

enum WorkMode: string
{
    case Remote = 'remote';
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
}
