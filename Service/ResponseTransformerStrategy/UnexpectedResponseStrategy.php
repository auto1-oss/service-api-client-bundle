<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\DeserializableResponseException;
use Auto1\ServiceAPIClientBundle\Service\Deserializer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

class UnexpectedResponseStrategy implements ResponseTransformerStrategy, LoggerAwareInterface
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
        return true; // catch-all
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
        $message = sprintf('Error response %s cannot be deserialized', $response->getStatusCode());

        $this->logger->debug($message, [
            'method' => $endpoint->getMethod(),
            'path' => $endpoint->getPath(),
            'requestClass' => $endpoint->getRequestClass(),
            'responseClass' => $endpoint->getResponseClass(),
            'response' => $responseBody,
        ]);

        $this->logGatewayError($endpoint, $response);

        throw new DeserializableResponseException($this->deserializer, new ErrorResponse($endpoint, $message, $response->getStatusCode(), $responseBody));
    }

    private function logGatewayError(EndpointInterface $endpoint, ResponseInterface $response)
    {
        switch ($response->getStatusCode()) {
            case Response::HTTP_GATEWAY_TIMEOUT:
                $message = 'service request failed due to 504 gateway timeout';
                break;
            case Response::HTTP_BAD_GATEWAY:
                $message = 'service request failed due to 502 bad gateway';
                break;
            case Response::HTTP_SERVICE_UNAVAILABLE:
                $message = 'service request failed due to 503 service unavailable';
                break;
            default:
                return;
        }

        $this->logger->error($message, [
            'dto' => $endpoint->getRequestClass(),
            'url' => "{$endpoint->getBaseUrl()}/{$endpoint->getPath()}",
        ]);
    }
}
