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

namespace Auto1\ServiceAPIClientBundle\Service\Request\Multipart;

use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Builds a multipart/form-data request body from a service request DTO.
 */
interface MultipartStreamFactoryInterface
{
    /**
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return MultipartStream The multipart body, exposing its boundary via
     *                         {@see MultipartStream::getBoundary()} and the `boundary`
     *                         stream metadata — consumers never read the body bytes.
     */
    public function create(ServiceRequestInterface $serviceRequest): MultipartStream;
}
