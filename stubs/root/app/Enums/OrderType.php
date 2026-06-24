<?php

namespace App\Enums;

enum OrderType: string
{
    case New = 'new';
    case Renewal = 'renewal';
    case Upgrade = 'upgrade';
    case Downgrade = 'downgrade';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New Purchase',
            self::Renewal => 'Renewal',
            self::Upgrade => 'Upgrade',
            self::Downgrade => 'Downgrade',
            self::Refund => 'Refund',
        };
    }
}
