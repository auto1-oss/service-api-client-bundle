<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Auto1\ServiceAPIClientBundle\Service\APIClient;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class APIClientTest.
 */
class APIClientTest extends TestCase
{
    /**
     * @var RequestFactoryInterface|ObjectProphecy
     */
    private $requestFactoryProphecy;

    /**
     * @var ResponseTransformerInterface|ObjectProphecy
     */
    private $responseTransformerProphecy;

    /**
     * @var HttpClient|ObjectProphecy
     */
    private $clientProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
        $this->responseTransformerProphecy = $this->prophesize(ResponseTransformerInterface::class);
        $this->clientProphecy = $this->prophesize(HttpClient::class);
    }

    public function testSend()
    {
        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->__call('getPath', [])->willReturn('somePath');
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $requestProphecy->__call('getUri', [])->willReturn($uriProphecy->reveal());
        $requestProphecy->__call('getHeaders', [])->willReturn(['someHeader']);
        $request = $requestProphecy->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $object = new \stdClass();

        /** @var ServiceRequestInterface $serviceRequest */
        $serviceRequest = $this->prophesize(ServiceRequestInterface::class)->reveal();

        $this->requestFactoryProphecy
            ->__call('create', [$serviceRequest])
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->clientProphecy
            ->__call('sendRequest', [$request])
            ->willReturn($response)
            ->shouldBeCalled()
        ;

        $this->responseTransformerProphecy
            ->__call('transform', [$response, $serviceRequest])
            ->willReturn($object)
            ->shouldBeCalled()
        ;

        $apiClient = new APIClient(
            $this->requestFactoryProphecy->reveal(),
            $this->responseTransformerProphecy->reveal(),
            $this->clientProphecy->reveal()
        );

        $result = $apiClient->send($serviceRequest);
        self::assertSame($object, $result);
    }
}
