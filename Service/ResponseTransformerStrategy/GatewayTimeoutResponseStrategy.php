<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\GatewayTimeoutResponseException;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

class GatewayTimeoutResponseStrategy implements ResponseTransformerStrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function supports(ResponseInterface $response): bool
    {
        return $response->getStatusCode() === Response::HTTP_GATEWAY_TIMEOUT;
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
        $message = 'service request failed due to 504 gateway timeout';

        $this->logger->error($message, [
            'dto' => $endpoint->getRequestClass(),
            'url' => "{$endpoint->getBaseUrl()}/{$endpoint->getPath()}",
        ]);

        throw new GatewayTimeoutResponseException(new ErrorResponse($endpoint, $message, $response->getStatusCode(), $responseBody));
    }

    public static function getDefaultPriority(): int
    {
        return -50;
    }
}
