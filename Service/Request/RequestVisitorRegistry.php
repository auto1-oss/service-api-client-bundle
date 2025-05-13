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
 * Class RequestVisitorRegistry.
 */
class RequestVisitorRegistry implements RequestVisitorRegistryInterface
{
    /**
     * @var string[][]
     */
    private $requestVisitorsByRequestFormat = [];

    /**
     * @var RequestVisitorInterface[]
     */
    private $requestVisitorsCommon = [];

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function registerRequestVisitor(RequestVisitorInterface $requestVisitor, ?string $requestFormat = null): self
    {
        if (!$requestFormat) {
            $this->requestVisitorsCommon[] = $requestVisitor;
        } else {
            $this->requestVisitorsByRequestFormat[$requestFormat][] = $requestVisitor;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegisteredRequestVisitors(string $requestFormat): array
    {
        return array_merge(
            $this->requestVisitorsByRequestFormat[$requestFormat] ?? [],
            $this->requestVisitorsCommon
        );
    }
}
