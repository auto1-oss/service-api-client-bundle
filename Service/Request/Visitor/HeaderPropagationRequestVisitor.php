<?php

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HeaderPropagationRequestVisitor.
 */
class HeaderPropagationRequestVisitor implements RequestVisitorInterface
{
    /**
     * @var Request|null
     */
    private $previousRequest;

    /**
     * @var string[]|null
     */
    private $headerNamesToPropagate;

    /**
     * HeaderPropagationRequestVisitor constructor.
     *
     * @param Request|null  $previousRequest
     * @param string[]|null $headerNamesToPropagate
     */
    public function __construct(Request $previousRequest = null, array $headerNamesToPropagate = null)
    {
        $this->previousRequest = $previousRequest;
        $this->headerNamesToPropagate = $headerNamesToPropagate;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(RequestInterface $request): RequestInterface
    {
        if (null === $this->previousRequest) {
            return $request;
        }

        if (empty($this->headerNamesToPropagate)) {
            return $request;
        }

        $headerBag = $this->previousRequest->headers;
        if (null === $headerBag) {
            return $request;
        }

        foreach ($this->headerNamesToPropagate as $headerName) {
            if ($headerBag->has($headerName)) {
                $headerValue = $headerBag->get($headerName);
                $request = $request->withHeader($headerName, $headerValue);
            }
        }

        return $request;
    }
}
