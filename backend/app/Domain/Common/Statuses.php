<?php

namespace App\Domain\Common;

final class Statuses
{
    public const PRODUCT_STATUSES = ['draft', 'active', 'archived'];

    public const VARIANT_STATUSES = ['active', 'inactive'];

    public const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'expired'];

    public const PAYMENT_STATUSES = ['unpaid', 'partially_paid', 'paid', 'refunded', 'partially_refunded'];

    public const FULFILLMENT_STATUSES = ['unfulfilled', 'preparing', 'shipped', 'delivered', 'returned'];

    public const PAYMENT_RECORD_STATUSES = ['pending', 'confirmed', 'failed', 'refunded'];

    public const PAYMENT_KINDS = ['full', 'deposit', 'balance', 'refund'];

    public const PAYMENT_METHODS = ['cod', 'bank_transfer', 'gateway'];

    public const CAMPAIGN_STATUSES = ['draft', 'open', 'closed', 'arrived', 'cancelled'];

    public const RESERVATION_STATUSES = ['awaiting_deposit', 'deposit_paid', 'balance_due', 'completed', 'expired', 'cancelled'];

    public const CMS_STATUSES = ['draft', 'in_review', 'published', 'archived'];

    public const MEDIA_STATUSES = ['pending', 'ready', 'failed'];

    public const MOVEMENT_TYPES = ['receipt', 'reserve', 'release', 'ship', 'adjust', 'preorder_hold', 'preorder_release'];

    public const ORDER_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled', 'expired'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'return_requested'],
        'delivered' => ['completed', 'return_requested'],
        'completed' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::ORDER_TRANSITIONS[$from] ?? [], true);
    }
}
