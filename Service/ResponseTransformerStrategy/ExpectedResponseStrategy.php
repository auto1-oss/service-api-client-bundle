<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\Service\Deserializer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ExpectedResponseStrategy implements ResponseTransformerStrategy, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $deserializer;

    public function __construct(
        Deserializer $deserializer
    ) {
        $this->deserializer = $deserializer;
        $this->setLogger(new NullLogger());
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
}
