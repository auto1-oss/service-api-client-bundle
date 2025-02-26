<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\ClientLogger;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientLoggerInterface
{
    public function logRequest(ServiceRequestInterface $serviceRequest, RequestInterface $request): void;

    public function logResponse(
        ServiceRequestInterface $serviceRequest,
        RequestInterface $request,
        ResponseInterface $response,
        int $durationInMs
    ): void;
}
