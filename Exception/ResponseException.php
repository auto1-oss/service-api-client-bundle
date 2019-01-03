<?php

namespace Auto1\ServiceAPIClientBundle\Exception;

use Auto1\ServiceAPIComponentsBundle\Exception\AbstractException;
use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;

/**
 * Class ResponseException
 */
class ResponseException extends AbstractException
{
    /**
     * @var ErrorResponse
     */
    protected $errorDto;

    /**
     * ResponseException constructor.
     *
     * @param ErrorResponse|null $errorDto
     * @param int $code
     * @param string|null $message
     * @param \Throwable|null $previous
     */
    public function __construct(ErrorResponse $errorDto = null, $code = 0, $message = null, \Throwable $previous = null)
    {
        parent::__construct($message ?: ($errorDto ? $errorDto->getMessage() : null), $code, $previous);
    }
}
