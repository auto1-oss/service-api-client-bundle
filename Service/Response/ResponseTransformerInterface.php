<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
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
