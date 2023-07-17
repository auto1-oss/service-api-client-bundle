<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Exception\Response;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\ResponseException;

class GatewayTimeoutResponseException extends ResponseException
{
    private $errorResponse;

    public function __construct(
        ErrorResponse $errorResponse,
        string $message = '',
        \Throwable $previous = null
    ) {
        $this->errorResponse = $errorResponse;
        parent::__construct(
            null,
            $this->errorResponse->status,
            $message,
            $previous
        );
    }
}
