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
use LogicException;

/**
 * Returns the first registered factory that supports the request/endpoint.
 *
 * Factories are iterated in registration (priority) order, so more specific
 * factories must be registered ahead of the default one.
 */
class RequestBodyFactoryRegistry implements RequestBodyFactoryRegistryInterface
{
    /**
     * @var iterable<RequestBodyFactoryInterface>
     */
    private $factories;

    /**
     * @param iterable<RequestBodyFactoryInterface> $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function getFactory(
        ServiceRequestInterface $serviceRequest,
        EndpointInterface $endpoint
    ): RequestBodyFactoryInterface {
        foreach ($this->factories as $factory) {
            if ($factory->supports($serviceRequest, $endpoint)) {
                return $factory;
            }
        }

        throw new LogicException(sprintf(
            'No request body factory supports request format "%s". '
            . 'A default %s should always be registered.',
            $endpoint->getRequestFormat(),
            RequestBodyFactoryInterface::class
        ));
    }
}
