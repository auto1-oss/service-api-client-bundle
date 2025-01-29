<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ClientLogger;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class NullClientLogger implements ClientLoggerInterface
{
    public function logRequest(ServiceRequestInterface $serviceRequest, RequestInterface $request): void
    {
        // noop
    }

    public function logResponse(ServiceRequestInterface $serviceRequest, RequestInterface $request, ResponseInterface $response, int $durationInMs): void
    {
        // noop
    }
}
