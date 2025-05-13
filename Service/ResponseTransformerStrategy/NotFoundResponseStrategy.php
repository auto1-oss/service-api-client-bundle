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

use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotFoundException;
use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

class NotFoundResponseStrategy implements ResponseTransformerStrategyInterface, LoggerAwareInterface
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
        return $response->getStatusCode() === Response::HTTP_NOT_FOUND;
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
        $errorDTO = $responseBody !== ''
            ? $this->deserializer->deserialize(
                $endpoint,
                ErrorResponse::class,
                $responseBody
            )
            : null;

        $message = $endpoint->getResponseClass() === null
            ? sprintf(
                '%s %s%s returned not found',
                $endpoint->getMethod(),
                $endpoint->getBaseUrl(),
                $endpoint->getPath()
            ) : sprintf('%s not found', $endpoint->getResponseClass());

        $this->logger->debug($message, [
            'method' => $endpoint->getMethod(),
            'path' => $endpoint->getPath(),
            'requestClass' => $endpoint->getRequestClass(),
            'responseClass' => $endpoint->getResponseClass(),
            'errorDTO' => $errorDTO,
        ]);

        throw new NotFoundException($errorDTO, $response->getStatusCode(), $message);
    }

    public static function getDefaultPriority(): int
    {
        return -20;
    }
}
