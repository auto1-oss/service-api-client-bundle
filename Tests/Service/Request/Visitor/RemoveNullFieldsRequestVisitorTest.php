<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\EndpointConfiguration;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\RemoveNullFieldsRequestVisitor;
use Http\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class RemoveNullFieldsRequestVisitorTest.
 */
class RemoveNullFieldsRequestVisitorTest extends TestCase
{
    /**
     * @var RequestInterface|ObjectProphecy
     */
    private $requestProphecy;

    /**
     * @var StreamFactory|ObjectProphecy
     */
    private $streamFactoryProfecy;

    /**
     * @var UriInterface|ObjectProphecy
     */
    private $uriInterfaceProfecy;

    /**
     * @var StreamInterface|ObjectProphecy
     */
    private $streamInterfaceProfecy;

    protected function setUp()
    {
        $this->requestProphecy = $this->prophesize(RequestInterface::class);
        $this->streamFactoryProfecy = $this->prophesize(StreamFactory::class);
        $this->uriInterfaceProfecy = $this->prophesize(UriInterface::class);
        $this->streamInterfaceProfecy = $this->prophesize(StreamInterface::class);
    }

    /**
     * @dataProvider provider
     */
    public function testRemoveNullFields($input, $expected)
    {
        $this->requestProphecy->__call('withBody', [Argument::type(StreamInterface::class)])
            ->shouldBeCalledOnce()
            ->willReturn($this->requestProphecy);

        $this->requestProphecy->__call('getMethod', [])
            ->willReturn("PUT");

        $this->requestProphecy->__call('getUri', [])
            ->willReturn($this->uriInterfaceProfecy);

        $this->uriInterfaceProfecy->__call('getPath', [])
            ->willReturn("/v1/some/1");

        $this->streamInterfaceProfecy->__call('getContents', [])
            ->willReturn($input);
        $this->streamInterfaceProfecy->reveal();

        $this->requestProphecy->__call('getBody', [])
            ->willReturn($this->streamInterfaceProfecy);

        $this->streamFactoryProfecy->__call('createStream', [Argument::is($expected)])
            ->willReturn($this->streamInterfaceProfecy);

        $request = $this->requestProphecy->reveal();
        $factory = $this->streamFactoryProfecy->reveal();
        $removeNullFieldsRequestVisitor = new RemoveNullFieldsRequestVisitor(
            $factory,
            [
                new EndpointConfiguration("PUT", "/v1/some/{id}")
            ]
        );

        $removeNullFieldsRequestVisitor->visit($request);
    }

    public function provider()
    {
        return [
            'remove first level null' => [
                json_encode(["a" => 1, "b" => null]),
                json_encode(["a" => 1])
            ], 'remove sub level null' => [
                json_encode(["a" => 1, "b" => [ "c" => 2, "d" => null]]),
                json_encode(["a" => 1, "b" => [ "c" => 2 ]])
            ],
            'leaves key empty when all childs are null' => [
                json_encode(["a" => 1, "b" => [ "c" => null, "d" => null]]),
                json_encode(["a" => 1, "b" => []])
            ],
            'associative array' => [
                json_encode(["test" =>  ["some", ["some" => null], 2]]),
                json_encode(["test" =>  ["some", [], 2]])
            ]
        ];
    }

    public function testNotConfigureEndpoinsWillNotBeProcess()
    {
        $this->requestProphecy->__call('withBody', [Argument::type(StreamInterface::class)])
            ->shouldNotBeCalled();

        $this->requestProphecy->__call('getMethod', [])
            ->willReturn("GET");

        $this->requestProphecy->__call('getUri', [])
            ->willReturn($this->uriInterfaceProfecy);

        $this->uriInterfaceProfecy->__call('getPath', [])
            ->willReturn("/v1/some/1");

        $request = $this->requestProphecy->reveal();
        $factory = $this->streamFactoryProfecy->reveal();
        $removeNullFieldsRequestVisitor = new RemoveNullFieldsRequestVisitor(
            $factory,
            [
                new EndpointConfiguration("PUT", "/v1/some/{id}")
            ]
        );

        $removeNullFieldsRequestVisitor->visit($request);
    }
}
