<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotAuthorizedException;
use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedResponseStrategy implements ResponseTransformerStrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $deserializer;

    public function __construct(
        DeserializerInterface $deserializer
    ) {
        $this->deserializer = $deserializer;
        $this->setLogger(new NullLogger());
    }

    public function supports(ResponseInterface $response): bool
    {
        return $response->getStatusCode() === Response::HTTP_UNAUTHORIZED;
    }

    /**
     * @param EndpointInterface $endpoint
     * @param ResponseInterface $response
     * @param string $responseBody
     *
     * @return object|object[]|string
     */
    public function handle(
        EndpointInterface $endpoint,
        ResponseInterface $response,
        string $responseBody
    ) {
        $errorDTO = $this->deserializer->deserialize(
            $endpoint,
            ErrorResponse::class,
            $responseBody,
        );

        $this->logger->debug($errorDTO->getMessage(), [
            'status' => $errorDTO->getStatus(),
            'path' => $errorDTO->getPath(),
            'errorKeyString' => $errorDTO->getError(),
            'requestClass' => $endpoint->getRequestClass(),
            'responseClass' => $endpoint->getResponseClass(),
        ]);

        throw new NotAuthorizedException($errorDTO, $response->getStatusCode(), $errorDTO->getMessage());
    }

    public static function getDefaultPriority(): int
    {
        return 40;
    }
}
