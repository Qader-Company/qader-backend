<?php

namespace App\Enums;

enum SeniorityLevel: string
{
    case Intern = 'intern';
    case Junior = 'junior';
    case MidLevel = 'mid_level';
    case Senior = 'senior';
    case Lead = 'lead';
}
