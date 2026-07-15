<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public int $productId;
    public int $available;
    public int $requested;

    public function __construct(int $productId, string $productName, int $available, int $requested)
    {
        $this->productId = $productId;
        $this->available = max(0, $available);
        $this->requested = $requested;

        parent::__construct(
            "Insufficient stock for \"{$productName}\": requested {$requested}, available {$this->available}."
        );
    }
}
