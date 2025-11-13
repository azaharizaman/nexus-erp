<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

/**
 * Invalid Resignation Exception
 * 
 * Thrown when invalid resignation data or operations are attempted.
 */
class InvalidResignationException extends Exception
{
    /**
     * Create a new invalid resignation exception.
     */
    public function __construct(string $message = 'Invalid resignation operation', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}