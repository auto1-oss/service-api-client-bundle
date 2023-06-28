<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIComponentsBundle\Exception\AbstractException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponseTransformerStrategy
{
    public function supports(ResponseInterface $response): bool;

    /**
     * @return object|object[]|string
     *
     * @throws AbstractException
     */
    public function handle(
        EndpointInterface $endpoint,
        ResponseInterface $response,
        string $responseBody
    );
}
