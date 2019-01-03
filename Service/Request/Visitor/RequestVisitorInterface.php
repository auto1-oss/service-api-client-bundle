<?php

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Psr\Http\Message\RequestInterface;

/**
 * Interface RequestVisitorInterface.
 */
interface RequestVisitorInterface
{
    /**
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    public function visit(RequestInterface $request): RequestInterface;
}
