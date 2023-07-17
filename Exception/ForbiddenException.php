<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Exception;

use Throwable;

class ForbiddenException extends ServiceException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
