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
