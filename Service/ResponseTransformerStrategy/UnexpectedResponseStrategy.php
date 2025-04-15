<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\DeserializableResponseException;
use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class UnexpectedResponseStrategy implements ResponseTransformerStrategyInterface, LoggerAwareInterface
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
            'url' => "{$endpoint->getBaseUrl()}/{$endpoint->getPath()}",
            'requestClass' => $endpoint->getRequestClass(),
            'responseClass' => $endpoint->getResponseClass(),
            'response' => $responseBody,
        ]);

        throw new DeserializableResponseException($this->deserializer, new ErrorResponse($endpoint, $message, $response->getStatusCode(), $responseBody));
    }

    public static function getDefaultPriority(): int
    {
        return -100;
    }
}
