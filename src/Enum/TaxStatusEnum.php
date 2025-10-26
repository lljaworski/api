<?php

declare(strict_types=1);

namespace App\Enum;

enum TaxStatusEnum: string
{
    case LIQUIDATION = 'Taxpayer in liquidation state';
    case RESTRUCTURING = 'Taxpayer undergoing restructuring proceedings';
    case BANKRUPTCY = 'Taxpayer in bankruptcy state';
    case INHERITANCE = 'Enterprise in inheritance';
}