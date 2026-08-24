<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public int $productId;
    public string $productName;
    public int $available;
    public int $requested;

    public function __construct(int $productId, string $productName, int $available, int $requested)
    {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->available = max(0, $available);
        $this->requested = $requested;

        parent::__construct(
            "Insufficient stock for \"{$productName}\": requested {$requested}, available {$this->available}."
        );
    }

    public function validationMessage(): string
    {
        return "{$this->productName} has only {$this->available} items available. You requested {$this->requested}.";
    }
}
