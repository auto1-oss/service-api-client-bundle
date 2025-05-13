<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
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
