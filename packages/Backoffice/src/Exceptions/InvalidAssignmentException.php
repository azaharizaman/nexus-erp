<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

/**
 * Invalid Assignment Exception
 * 
 * Thrown when attempting to make an invalid assignment (e.g., staff without office or department).
 */
class InvalidAssignmentException extends Exception
{
    public function __construct(string $message = 'Invalid assignment detected', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}