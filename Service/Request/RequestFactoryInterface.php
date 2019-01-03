<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Request;

use Psr\Http\Message\RequestInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Should build PSR Request from data provided.
 *
 * Interface RequestFactoryInterface
 */
interface RequestFactoryInterface
{
    /**
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return RequestInterface
     */
    public function create(ServiceRequestInterface $serviceRequest): RequestInterface;
}
