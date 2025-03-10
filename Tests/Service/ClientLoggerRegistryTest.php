<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Service\ClientLogger\ClientLoggerInterface;
use Auto1\ServiceAPIClientBundle\Service\ClientLoggerRegistry;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientLoggerRegistryTest extends TestCase
{
    public function testLogRequestCallsUnderlyingLoggers(): void
    {
        $serviceRequest = $this->createMock(ServiceRequestInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $registry = new ClientLoggerRegistry();
        $loggers = [
            $this->createMock(ClientLoggerInterface::class),
            $this->createMock(ClientLoggerInterface::class),
            $this->createMock(ClientLoggerInterface::class),
        ];

        foreach ($loggers as $logger) {
            $registry->registerLogger($logger);
            $logger
                ->expects(self::once())
                ->method('logRequest')
                ->with($serviceRequest, $request);
        }

        $registry->logRequest($serviceRequest, $request);
    }

    public function testLogResponseCallsUnderlyingLoggers(): void
    {
        $serviceRequest = $this->createMock(ServiceRequestInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $registry = new ClientLoggerRegistry();
        $loggers = [
            $this->createMock(ClientLoggerInterface::class),
            $this->createMock(ClientLoggerInterface::class),
            $this->createMock(ClientLoggerInterface::class),
        ];

        $expectedDuration = 123;

        foreach ($loggers as $logger) {
            $registry->registerLogger($logger);
            $logger
                ->expects(self::once())
                ->method('logResponse')
                ->with($serviceRequest, $request, $response, $expectedDuration);
        }

        $registry->logResponse($serviceRequest, $request, $response, $expectedDuration);
    }
}
