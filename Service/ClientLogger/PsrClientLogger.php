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
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logRequest(ServiceRequestInterface $serviceRequest, RequestInterface $request): void
    {
    }

    public function logResponse(ServiceRequestInterface $serviceRequest, RequestInterface $request, ResponseInterface $response, int $durationInMs): void
    {
        $this->logger->log(
            $this->selectLogLevel($response, $durationInMs),
            'HttpClient request time (ms)',
            [
                'requestPath' => $request->getUri()->getPath(),
                'requestTime' => $durationInMs,
            ],
        );
    }

    private function selectLogLevel(ResponseInterface $response, int $durationInMs): string
    {
        return LogLevel::DEBUG; // @todo mcz
    }
}
