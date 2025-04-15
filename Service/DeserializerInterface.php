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

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;

interface DeserializerInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws MalformedResponseException
     */
    public function deserialize(EndpointInterface $endpoint, string $className, string $data): object;

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T[]
     *
     * @throws MalformedResponseException
     */
    public function deserializeAsArray(EndpointInterface $endpoint, string $className, string $data): array;
}
