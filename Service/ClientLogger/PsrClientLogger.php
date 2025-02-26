<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ClientLogger;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PsrClientLogger implements ClientLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string One of LogLevel::* constants
     */
    private $requestTimeLogLevel;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        string $requestTimeLogLevel = LogLevel::DEBUG
    ) {
        $this->logger = $logger;
        $this->requestTimeLogLevel = $requestTimeLogLevel;
    }

    public function logRequest(ServiceRequestInterface $serviceRequest, RequestInterface $request): void
    {
    }

    public function logResponse(
        ServiceRequestInterface $serviceRequest,
        RequestInterface $request,
        ResponseInterface $response,
        int $durationInMs
    ): void {
        $this->logger->log(
            $this->requestTimeLogLevel,
            'HttpClient request time (ms)',
            [
                'requestClass' => get_class($serviceRequest),
                'requestPath' => $request->getUri()->getPath(),
                'requestTime' => $durationInMs,
            ],
        );
    }
}
