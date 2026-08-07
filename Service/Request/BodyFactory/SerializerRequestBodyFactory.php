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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Default factory: serializes the request with the endpoint's request format
 * (json, url, json-patch, ...). Supports every request, so it must be registered
 * last in the registry.
 */
class SerializerRequestBodyFactory implements RequestBodyFactoryInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint)
    {
        return $this->serializer->serialize($serviceRequest, $endpoint->getRequestFormat());
    }
}
