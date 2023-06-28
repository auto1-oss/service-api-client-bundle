<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;

interface Deserializer
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
