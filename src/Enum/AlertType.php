<?php

declare(strict_types=1);

namespace App\Enum;

enum AlertType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case BalanceAbove = 'balance_above';
    case BalanceBelow = 'balance_below';
}
