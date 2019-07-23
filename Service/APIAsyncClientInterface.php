<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Exception;
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
     *
     * @throws Exception
     */
    public function sendAsync(ServiceRequestInterface $request);
}
