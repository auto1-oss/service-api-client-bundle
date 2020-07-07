<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIComponentsBundle\Exception\Request\InvalidArgumentException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestVisitorRegistry;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestVisitorRegistryInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\RequestVisitorInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactory;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Class RequestFactoryTest.
 */
class RequestFactoryTest extends TestCase
{
    /**
     * @var ServiceRequestInterface|ObjectProphecy
     */
    private $serviceRequestProphecy;

    /**
     * @var EndpointRegistryInterface|ObjectProphecy
     */
    private $endpointRegistryProphecy;

    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private $serializerProphecy;

    /**
     * @var RequestVisitorRegistry|ObjectProphecy
     */
    private $requestVisitorRegistryProphecy;

    /**
     * @var RequestVisitorInterface|ObjectProphecy
     */
    private $requestDecoratorProphecy;

    /**
     * @var UriFactory|ObjectProphecy
     */
    private $uriFactoryProphecy;

    /**
     * @var MessageFactory|ObjectProphecy
     */
    private $messageFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->serviceRequestProphecy = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy = $this->prophesize(EndpointRegistryInterface::class);
        $this->serializerProphecy = $this->prophesize(SerializerInterface::class);
        $this->requestVisitorRegistryProphecy = $this->prophesize(RequestVisitorRegistryInterface::class);
        $this->requestDecoratorProphecy = $this->prophesize(RequestVisitorInterface::class);
        $this->uriFactoryProphecy = $this->prophesize(UriFactory::class);
        $this->messageFactoryProphecy = $this->prophesize(MessageFactory::class);
    }

    /**
     * @return void
     */
    public function testBuildFlow()
    {
        $baseUrl = 'baseUrl';
        $routeString = 'routeString';
        $requestMethod = 'GET';
        $requestBody = '{requestBody:requestBody}';
        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->__call('getBaseUrl', [])
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getPath', [])
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getMethod', [])
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getRequestFormat', [])
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $request = $this->prophesize(RequestInterface::class)->reveal();

        $this->endpointRegistryProphecy
            ->__call('getEndpoint', [Argument::type(ServiceRequestInterface::class)])
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->__call('serialize', [Argument::type(ServiceRequestInterface::class), EndpointInterface::FORMAT_JSON])
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->__call('createUri', [$baseUrl.$routeString])
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->messageFactoryProphecy
            ->__call('createRequest', [
                $requestMethod,
                $uri,
                [],
                $requestBody,
            ])
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->requestVisitorRegistryProphecy->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([$this->requestDecoratorProphecy->reveal(), $this->requestDecoratorProphecy->reveal()])
            ->shouldBeCalled()
        ;

        $this->requestDecoratorProphecy
            ->__call('visit', [Argument::type(RequestInterface::class)])
            ->willReturn($request)
            ->shouldBeCalledTimes(2)
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->messageFactoryProphecy->reveal()
        );

        self::assertInstanceOf(
            RequestInterface::class,
            $requestBuilder->create($this->serviceRequestProphecy->reveal())
        );
    }

    /**
     * @return void
     */
    public function testBuildFlowWithQueryParams()
    {
        $baseUrl = 'baseUrl';
        $routeString = '/routeString?param={param}';
        $originParamValue = 'value with whitespaces';
        $requestMethod = 'GET';
        $requestBody = '{requestBody:requestBody}';

        $expectedUri = 'baseUrl/routeString?param=value+with+whitespaces';

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->__call('getBaseUrl', [])
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getPath', [])
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getMethod', [])
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy->__call('getRequestFormat', [])
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $request = $this->prophesize(RequestInterface::class)->reveal();

        $this->endpointRegistryProphecy
            ->__call('getEndpoint', [Argument::type(ServiceRequestInterface::class)])
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->__call('serialize', [Argument::type(ServiceRequestInterface::class), EndpointInterface::FORMAT_JSON])
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->__call('createUri', [$expectedUri])
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->messageFactoryProphecy
            ->__call('createRequest', [
                $requestMethod,
                $uri,
                [],
                $requestBody,
            ])
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->requestVisitorRegistryProphecy->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([])
            ->shouldBeCalled()
        ;

        $this->requestDecoratorProphecy
            ->__call('visit', [Argument::type(RequestInterface::class)])
            ->shouldNotBeCalled()
        ;

        // Mock non existing method of ServiceRequest `getParam`
        $serviceRequest = $this->getMockBuilder(ServiceRequestInterface::class)
            ->setMethods(['getParam'])
            ->getMock()
        ;

        $serviceRequest->expects($this->once())
            ->method('getParam')
            ->willReturn($originParamValue)
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->messageFactoryProphecy->reveal()
        );

        self::assertInstanceOf(
            RequestInterface::class,
            $requestBuilder->create($serviceRequest)
        );
    }

    /**
     * @return void
     */
    public function testBuildFlowValidationFailsOnUnmappedRequestArguments()
    {
        $this->expectException(InvalidArgumentException::class);

        $baseUrl = 'baseUrl';
        $routeString = 'routeString\{invalidArgument}';
        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getBaseUrl()
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getPath()
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint($this->serviceRequestProphecy->reveal())
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->messageFactoryProphecy->reveal()
        );

        self::assertInstanceOf(
            RequestInterface::class,
            $requestBuilder->create($this->serviceRequestProphecy->reveal())
        );
    }
}
