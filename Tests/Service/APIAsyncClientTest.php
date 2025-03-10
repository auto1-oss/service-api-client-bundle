<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Service\APIAsyncClient;
use Auto1\ServiceAPIClientBundle\Service\ClientLoggerRegistry;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\RequestTimer;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Client\HttpAsyncClient;
use Http\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class APIAsyncClientTest.
 */
class APIAsyncClientTest extends TestCase
{
    /**
     * @var RequestTimer|ObjectProphecy
     */
    private $requestTimer;

    /**
     * @var ClientLoggerRegistry|ObjectProphecy
     */
    private $clientLoggerRegistry;

    /**
     * @var RequestFactoryInterface|ObjectProphecy
     */
    private $requestFactoryProphecy;

    /**
     * @var ResponseTransformerInterface|ObjectProphecy
     */
    private $responseTransformerProphecy;

    /**
     * @var HttpAsyncClient|ObjectProphecy
     */
    private $clientProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestTimer = $this->prophesize(RequestTimer::class);
        $this->clientLoggerRegistry = $this->prophesize(ClientLoggerRegistry::class);
        $this->requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
        $this->responseTransformerProphecy = $this->prophesize(ResponseTransformerInterface::class);
        $this->clientProphecy = $this->prophesize(HttpAsyncClient::class);
    }

    public function testSendAsync()
    {
        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('somePath');
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());
        $requestProphecy->getHeaders()->willReturn(['someHeader']);
        $request = $requestProphecy->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $object = new \stdClass();

        /** @var ServiceRequestInterface $serviceRequest */
        $serviceRequest = $this->prophesize(ServiceRequestInterface::class)->reveal();

        $this->requestTimer
            ->from($request)
            ->shouldBeCalled();

        $this->requestTimer
            ->to($request)
            ->willReturn($duration = 567)
            ->shouldBeCalled();

        $this->clientLoggerRegistry
            ->logRequest($serviceRequest, $request)
            ->shouldBeCalled();

        $this->clientLoggerRegistry
            ->logResponse($serviceRequest, $request, $response, $duration)
            ->shouldBeCalled();

        $this->requestFactoryProphecy
            ->create($serviceRequest)
            ->willReturn($request)
            ->shouldBeCalled();

        $promise = new FulfilledPromise($response);
        $this->clientProphecy
            ->sendAsyncRequest($request)
            ->willReturn($promise)
            ->shouldBeCalled();

        $this->responseTransformerProphecy
            ->transform($response, $serviceRequest)
            ->willReturn($object)
            ->shouldBeCalled();

        $apiClient = new APIAsyncClient(
            $this->requestTimer->reveal(),
            $this->clientLoggerRegistry->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->responseTransformerProphecy->reveal(),
            $this->clientProphecy->reveal()
        );

        $result = $apiClient->sendAsync($serviceRequest)->wait();

        self::assertSame($object, $result);
    }
}
