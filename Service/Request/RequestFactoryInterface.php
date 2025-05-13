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
