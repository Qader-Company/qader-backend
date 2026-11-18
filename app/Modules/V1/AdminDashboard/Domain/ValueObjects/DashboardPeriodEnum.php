<?php

namespace App\Modules\V1\AdminDashboard\Domain\ValueObjects;

enum DashboardPeriodEnum: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
