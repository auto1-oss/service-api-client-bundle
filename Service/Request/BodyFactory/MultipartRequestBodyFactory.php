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

use Auto1\ServiceAPIClientBundle\Service\Request\Multipart\MultipartStreamFactoryInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Builds a multipart/form-data body for endpoints with `requestFormat: multipart`.
 * Like the rest of the request building, it relies on a PSR-17 stream factory
 * discovered via `Psr17FactoryDiscovery`, so a PSR-7 implementation must be
 * installed.
 */
class MultipartRequestBodyFactory implements RequestBodyFactoryInterface
{
    /**
     * @var MultipartStreamFactoryInterface
     */
    private $multipartStreamFactory;

    /**
     * @param MultipartStreamFactoryInterface $multipartStreamFactory
     */
    public function __construct(MultipartStreamFactoryInterface $multipartStreamFactory)
    {
        $this->multipartStreamFactory = $multipartStreamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint): bool
    {
        return EndpointInterface::FORMAT_MULTIPART === $endpoint->getRequestFormat();
    }

    /**
     * {@inheritdoc}
     */
    public function create(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint)
    {
        return $this->multipartStreamFactory->create($serviceRequest);
    }
}
