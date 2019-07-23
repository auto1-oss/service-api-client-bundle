<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Promise\Promise;

/**
 * Interface APIAsyncClientInterface
 */
interface APIAsyncClientInterface
{
    /**
     * @param ServiceRequestInterface $request
     *
     * @return Promise
     */
    public function sendAsync(ServiceRequestInterface $request);
}
