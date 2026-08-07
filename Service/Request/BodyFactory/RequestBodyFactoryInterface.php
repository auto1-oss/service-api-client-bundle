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
 * Builds the HTTP request body for a service request, per content format.
 *
 * Implementations are resolved first-match by {@see RequestBodyFactoryRegistryInterface};
 * a new request format is supported by adding a tagged factory, with no change to
 * the request factory itself.
 */
interface RequestBodyFactoryInterface
{
    /**
     * @param ServiceRequestInterface $serviceRequest
     * @param EndpointInterface       $endpoint
     *
     * @return bool
     */
    public function supports(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint): bool;

    /**
     * @param ServiceRequestInterface $serviceRequest
     * @param EndpointInterface       $endpoint
     *
     * @return string|\Psr\Http\Message\StreamInterface|resource|null
     */
    public function create(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint);
}
