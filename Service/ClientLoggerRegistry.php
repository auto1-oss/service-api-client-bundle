<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Service\ClientLogger\ClientLoggerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientLoggerRegistry
{
    /**
     * @var ClientLoggerInterface[]
     */
    private $loggers;

    /**
     * @param ClientLoggerInterface[] $clientLoggers
     */
    public function __construct(
        iterable $clientLoggers = []
    ) {
        $this->loggers = $clientLoggers;
    }

    public function logRequest(ServiceRequestInterface $serviceRequest, RequestInterface $request): void
    {
        foreach ($this->loggers as $logger) {
            $logger->logRequest($serviceRequest, $request);
        }
    }

    public function logResponse(ServiceRequestInterface $serviceRequest, RequestInterface $request, ResponseInterface $response, int $durationInMs): void
    {
        foreach ($this->loggers as $logger) {
            $logger->logResponse($serviceRequest, $request, $response, $durationInMs);
        }
    }
}
