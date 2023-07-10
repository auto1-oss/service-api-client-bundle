<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Exception\Response;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\ResponseException;
use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Throwable;

class DeserializableResponseException extends ResponseException
{
    private $deserializer;
    private $errorResponse;

    public function __construct(
        DeserializerInterface $deserializer,
        ErrorResponse         $errorResponse,
        string                $message = '',
        Throwable             $previous = null
    ) {
        $this->errorResponse = $errorResponse;
        $this->deserializer = $deserializer;
        parent::__construct(
            null,
            $this->errorResponse->status,
            $message,
            $previous
        );
    }

    public function getErrorResponse(): ErrorResponse
    {
        return $this->errorResponse;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws MalformedResponseException
     */
    public function responseAs(string $className): object
    {
        return $this->deserializer->deserialize(
            $this->errorResponse->endpoint,
            $className,
            $this->errorResponse->body,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T[]
     *
     * @throws MalformedResponseException
     */
    public function responseAsArrayOf(string $className): array
    {
        return $this->deserializer->deserializeAsArray(
            $this->errorResponse->endpoint,
            $className,
            $this->errorResponse->body,
        );
    }
}
