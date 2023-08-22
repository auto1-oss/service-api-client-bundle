<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;

class ExpectedResponseStrategy implements ResponseTransformerStrategyInterface
{
    private $deserializer;

    public function __construct(
        DeserializerInterface $deserializer
    ) {
        $this->deserializer = $deserializer;
    }

    public function supports(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    /**
     * @param EndpointInterface $endpoint
     * @param ResponseInterface $response
     * @param string $responseBody
     *
     * @return array|mixed|object|object[]|string
     */
    public function handle(
        EndpointInterface $endpoint,
        ResponseInterface $response,
        string $responseBody
    ) {
        $className = $endpoint->getResponseClass();

        if (null === $className) {
            return $responseBody;
        }

        if (str_ends_with($className, '[]')) {
            /** @var class-string<object> $className */
            $className = substr($className, 0, -2);

            return $this->deserializer->deserializeAsArray(
                $endpoint,
                $className,
                $responseBody
            );
        }

        /** @var class-string<object> $className */
        return $this->deserializer->deserialize(
            $endpoint,
            $className,
            $responseBody
        );
    }

    public static function getDefaultPriority(): int
    {
        return -10;
    }
}
