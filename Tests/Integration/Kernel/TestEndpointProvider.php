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

namespace Auto1\ServiceAPIClientBundle\Tests\Integration\Kernel;

use Auto1\ServiceAPIClientBundle\Tests\Integration\Fixtures\JsonRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\ExtendedMultipartRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\MultipartRequestStub;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointImmutable;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointProviderInterface;

/**
 * Registers the endpoints used by the integration kernel (tagged
 * `auto1.api.endpoint_provider`, the same hook a real application uses).
 */
class TestEndpointProvider implements EndpointProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEndpoints(): array
    {
        return [
            new EndpointImmutable(
                'POST',
                'http://localhost',
                '/v1/documents',
                'multipart',
                MultipartRequestStub::class,
                'json',
                null,
                null
            ),
            new EndpointImmutable(
                'POST',
                'http://localhost',
                '/v1/extended-documents',
                'multipart',
                ExtendedMultipartRequestStub::class,
                'json',
                null,
                null
            ),
            new EndpointImmutable(
                'POST',
                'http://localhost',
                '/v1/widgets',
                'json',
                JsonRequestStub::class,
                'json',
                null,
                null
            ),
        ];
    }
}
