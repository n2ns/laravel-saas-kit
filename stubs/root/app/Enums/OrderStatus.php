<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartialRefund = 'partial_refund';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::PartialRefund => 'Partial Refund',
        };
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Paid, self::PartialRefund]);
    }
}
