<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Interface APIClientInterface
 */
interface APIClientInterface
{
    /**
     * @param ServiceRequestInterface $request
     *
     * @return object|object[]|string
     */
    public function send(ServiceRequestInterface $request);
}
