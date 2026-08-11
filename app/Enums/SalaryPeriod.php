<?php

namespace App\Enums;

enum SalaryPeriod: string
{
    case Hourly = 'hourly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
