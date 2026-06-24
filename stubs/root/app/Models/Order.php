<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    // Legacy constants for backward compatibility
    public const TYPE_NEW = 'new';

    public const TYPE_RENEWAL = 'renewal';

    public const TYPE_UPGRADE = 'upgrade';

    public const TYPE_DOWNGRADE = 'downgrade';

    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PARTIAL_REFUND = 'partial_refund';

    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_id',
        'gateway_id',
        'order_number',
        'type',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'refunded_amount',
        'currency',
        'provider_order_id',
        'provider_invoice_id',
        'provider_payment_id',
        'provider_data',
        'paid_at',
        'refunded_at',
        'refund_reason',
        'billing_snapshot',
    ];

    protected $hidden = [
        'provider_data',
        'billing_snapshot',
        'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'provider_data' => 'array',
            'billing_snapshot' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid && $this->paid_at !== null;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [OrderStatus::Refunded, OrderStatus::PartialRefund]);
    }

    public function canRefund(): bool
    {
        // Order must have been paid (paid_at set) and not fully refunded
        $hasBeenPaid = $this->paid_at !== null;
        $hasRefundableAmount = $this->refunded_amount < $this->total;
        $notFullyRefunded = $this->status !== OrderStatus::Refunded;

        return $hasBeenPaid && $hasRefundableAmount && $notFullyRefunded;
    }

    public function getRemainingRefundable(): float
    {
        return max(0, $this->total - $this->refunded_amount);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'DF';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}{$date}{$random}";
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }
}
