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

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Service\ClientLogger\ClientLoggerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientLoggerRegistry implements ClientLoggerInterface
{
    /**
     * @var ClientLoggerInterface[]
     */
    private $loggers = [];

    public function registerLogger(ClientLoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
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
