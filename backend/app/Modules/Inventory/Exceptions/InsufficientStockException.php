<?php

namespace App\Modules\Inventory\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = 'Insufficient stock available for this product variant', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
