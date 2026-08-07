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

namespace Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory;

use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Resolves the request body factory that applies to a given request/endpoint.
 */
interface RequestBodyFactoryRegistryInterface
{
    /**
     * @param ServiceRequestInterface $serviceRequest
     * @param EndpointInterface       $endpoint
     *
     * @return RequestBodyFactoryInterface
     */
    public function getFactory(
        ServiceRequestInterface $serviceRequest,
        EndpointInterface $endpoint
    ): RequestBodyFactoryInterface;
}
