<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Request;

use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\RequestVisitorInterface;

/**
 * Interface RequestVisitorRegistryInterface.
 */
interface RequestVisitorRegistryInterface
{
    /**
     * @param RequestVisitorInterface $requestVisitor
     * @param string                  $requestFormat
     *
     * @return mixed
     */
    public function registerRequestVisitor(RequestVisitorInterface $requestVisitor, string $requestFormat);

    /**
     * @param string $requestFormat
     *
     * @return RequestVisitorInterface[]
     */
    public function getRegisteredRequestVisitors(string $requestFormat): array;
}
