<?php
declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Http\Message\StreamFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class RemoveNullFieldsRequestVisitor.
 */
class RemoveNullFieldsRequestVisitor implements RequestVisitorInterface
{
    /**
     * @var EndpointConfiguration[]
     */
    private $endpoints;

    /**
     * @var StreamFactory
     */
    private $factory;

    /**
     * RemoveNullFieldsRequestVisitor constructor.
     *
     * @param StreamFactory $factory
     * @param EndpointConfiguration[] $endpoints
     */
    public function __construct(
        StreamFactory $factory,
        array $endpoints
    ) {
        $this->factory = $factory;
        $this->endpoints = $endpoints;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(RequestInterface $request): RequestInterface
    {
        if ($this->isCompatible($request)) {
            $request = $request->withBody($this->cleanBody($request->getBody()));
        }

        return $request;
    }

    /**
     * @return bool
     */
    private function isCompatible(RequestInterface $request): bool
    {
        foreach ($this->endpoints as $endpoint) {
            if (
                $request->getMethod() === $endpoint->getMethod()
                && preg_match($endpoint->getPathRegexp(), $request->getUri()->getPath())
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return StreamInterface
     */
    private function cleanBody(StreamInterface $requestBody): StreamInterface
    {
        $data = json_decode($requestBody->getContents(), true);
        $cleanData = $this->clean($data);

        return $this->factory->createStream(json_encode($cleanData));
    }

    /**
     * @param array $input
     *
     * @return array
     */
    private function clean($input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->clean($value);
            }
        }

        return array_filter($input, static function ($value) {
            return null !== $value;
        });
    }
}
