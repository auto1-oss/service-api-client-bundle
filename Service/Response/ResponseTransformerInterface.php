<?php

namespace Auto1\ServiceAPIClientBundle\Service\Response;

use Psr\Http\Message\ResponseInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Use ServiceRequest to get transformation settings for response
 *
 * Interface ResponseTransformerInterface
 */
interface ResponseTransformerInterface
{
    /**
     * @param ResponseInterface       $response
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return object|object[]|string
     */
    public function transform(ResponseInterface $response, ServiceRequestInterface $serviceRequest);
}
