<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when a stock operation cannot be satisfied.
 *
 * Distinct from a generic failure so controllers can surface the real reason to
 * the cashier - "6kg Gas Cylinder is out of stock" - instead of collapsing it
 * into "an unexpected error occurred". This is a client-correctable condition,
 * so it maps to 422 rather than 500.
 */
class InsufficientStockException extends Exception
{
    /** @var array<int, array{product_id:int, product_name:string, available:int, requested:int}> */
    protected array $shortages;

    public function __construct(string $message, array $shortages = [])
    {
        parent::__construct($message);

        $this->shortages = $shortages;
    }

    /**
     * Per-product detail so the terminal can highlight the offending lines.
     */
    public function shortages(): array
    {
        return $this->shortages;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => 'insufficient_stock',
            'shortages' => $this->shortages,
        ];
    }
}
