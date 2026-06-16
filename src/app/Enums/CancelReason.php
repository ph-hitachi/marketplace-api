<?php

namespace App\Enums;

enum CancelReason: int
{
    /** Customer changed their mind */
    case CUSTOMER_CHANGE_OF_MIND = 1;
    /** The shipping address was incorrect */
    case WRONG_SHIPPING_ADDRESS = 2;
    /** Duplicate order placed by mistake */
    case ORDER_DUPLICATION = 3;
    /** Seller took too long to ship */
    case SELLER_DELAY = 4;
    /** Other reasons (requires notes) */
    case OTHER = 5;

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER_CHANGE_OF_MIND => 'Customer change of mind',
            self::WRONG_SHIPPING_ADDRESS => 'Wrong shipping address',
            self::ORDER_DUPLICATION => 'Order duplication',
            self::SELLER_DELAY => 'Seller delay',
            self::OTHER => 'Other',
        };
    }
}
