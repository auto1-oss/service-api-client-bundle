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
use Psr\Http\Message\StreamInterface;

/**
 * Uses the request verbatim as the body when it already is a PSR-7 stream
 * (e.g. a whole-body binary upload).
 *
 * Endpoints declared with `requestFormat: multipart` are excluded: their declared
 * format wins, so the DTO goes to the multipart body factory and the body matches
 * the multipart Content-Type visitor keyed on that format.
 */
class StreamRequestBodyFactory implements RequestBodyFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint): bool
    {
        return $serviceRequest instanceof StreamInterface
            && EndpointInterface::FORMAT_MULTIPART !== $endpoint->getRequestFormat();
    }

    /**
     * {@inheritdoc}
     */
    public function create(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint)
    {
        return $serviceRequest;
    }
}
