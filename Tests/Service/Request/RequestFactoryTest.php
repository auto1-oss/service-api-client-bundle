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

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIComponentsBundle\Exception\Request\InvalidArgumentException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface as PsrStreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface as PsrUriFactoryInterface;
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
     * @var PsrUriFactoryInterface|ObjectProphecy
     */
    private $uriFactoryProphecy;

    /**
     * @var PsrRequestFactoryInterface|ObjectProphecy
     */
    private $requestFactoryProphecy;

    /**
     * @var PsrStreamFactoryInterface|ObjectProphecy
     */
    private $streamFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serviceRequestProphecy = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy = $this->prophesize(EndpointRegistryInterface::class);
        $this->serializerProphecy = $this->prophesize(SerializerInterface::class);
        $this->requestVisitorRegistryProphecy = $this->prophesize(RequestVisitorRegistryInterface::class);
        $this->requestDecoratorProphecy = $this->prophesize(RequestVisitorInterface::class);
        $this->uriFactoryProphecy = $this->prophesize(PsrUriFactoryInterface::class);
        $this->requestFactoryProphecy = $this->prophesize(PsrRequestFactoryInterface::class);
        $this->streamFactoryProphecy = $this->prophesize(PsrStreamFactoryInterface::class);
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
        $endpointProphecy->getBaseUrl()
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getPath()
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getMethod()
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getRequestFormat()
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();
        $stream = $this->prophesize(StreamInterface::class)->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint($this->serviceRequestProphecy->reveal())
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->serialize($this->serviceRequestProphecy->reveal(), EndpointInterface::FORMAT_JSON)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($baseUrl . $routeString)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->requestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->streamFactoryProphecy
            ->createStream($requestBody)
            ->willReturn($stream)
            ->shouldBeCalled()
        ;

        $requestProphecy
            ->withBody($stream)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->requestVisitorRegistryProphecy
            ->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([$this->requestDecoratorProphecy->reveal(), $this->requestDecoratorProphecy->reveal()])
            ->shouldBeCalled()
        ;

        $this->requestDecoratorProphecy
            ->visit($request)
            ->willReturn($request)
            ->shouldBeCalledTimes(2)
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            false
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
        $routeString = '/routeString?first-param={firstParam}&second-param=ignored value';
        $originParamValue = 'value with whitespaces';
        $requestMethod = 'GET';
        $requestBody = '';

        $expectedUri = 'baseUrl/routeString?first-param=value+with+whitespaces&second-param=ignored value';

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getBaseUrl()
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getPath()
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getMethod()
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getRequestFormat()
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();
        $stream = $this->prophesize(StreamInterface::class)->reveal();

        // Mock non existing method of ServiceRequest `getParam`
        $serviceRequest = $this->getMockBuilder(ServiceRequestInterface::class)
            ->setMethods(['getFirstParam'])
            ->getMock();

        $serviceRequest
            ->method('getFirstParam')
            ->willReturn($originParamValue);

        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->serialize($serviceRequest, EndpointInterface::FORMAT_JSON)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($expectedUri)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->requestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->streamFactoryProphecy
            ->createStream($requestBody)
            ->willReturn($stream)
            ->shouldBeCalled()
        ;

        $requestProphecy
            ->withBody($stream)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->requestVisitorRegistryProphecy
            ->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([])
            ->shouldBeCalled()
        ;

        $this->requestDecoratorProphecy
            ->visit($request)
            ->shouldNotBeCalled()
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            false
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
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            false
        );

        self::assertInstanceOf(
            RequestInterface::class,
            $requestBuilder->create($this->serviceRequestProphecy->reveal())
        );
    }

    /**
     * @return void
     */
    public function testBuildFlowWithUnderscoreAndDashParams(): void
    {
        $baseUrl = 'baseUrl';
        $routeString = '/{first-param}?second_param={second_param}&third-param={thirdParam}';
        $firstParamValue = 12345;
        $secondParamValue = 'value with whitespaces';
        $thirdParamValue = 'https://www.auto1.com/';
        $requestMethod = 'GET';
        $requestBody = '';

        $expectedUri = 'baseUrl/12345?second_param=value+with+whitespaces&third-param=https%3A%2F%2Fwww.auto1.com%2F';

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy
            ->getBaseUrl()
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy
            ->getPath()
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy
            ->getMethod()
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy
            ->getRequestFormat()
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();
        $stream = $this->prophesize(StreamInterface::class)->reveal();

        // Mock non existing method of ServiceRequest `getParam`
        $serviceRequest = $this->getMockBuilder(ServiceRequestInterface::class)
            ->addMethods(['getFirstParam', 'getSecondParam', 'getThirdParam'])
            ->getMock();

        $serviceRequest
            ->expects($this->once())
            ->method('getFirstParam')
            ->willReturn($firstParamValue);

        $serviceRequest
            ->expects($this->once())
            ->method('getSecondParam')
            ->willReturn($secondParamValue);

        $serviceRequest
            ->expects($this->once())
            ->method('getThirdParam')
            ->willReturn($thirdParamValue);


        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->serialize($serviceRequest, EndpointInterface::FORMAT_JSON)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($expectedUri)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->requestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->streamFactoryProphecy
            ->createStream($requestBody)
            ->willReturn($stream)
            ->shouldBeCalled()
        ;

        $requestProphecy
            ->withBody($stream)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        $this->requestVisitorRegistryProphecy
            ->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([])
            ->shouldBeCalled()
        ;

        $this->requestDecoratorProphecy
            ->visit($request)
            ->shouldNotBeCalled()
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            false
        );

        $this->assertInstanceOf(RequestInterface::class, $requestBuilder->create($serviceRequest));
    }

    /**
     * In strict mode a body-less method (GET/HEAD/OPTIONS/TRACE) must produce no request body,
     * so neither the stream factory nor `withBody()` should be touched.
     *
     * @return void
     */
    public function testBuildFlowSkipsBodyForBodilessMethodInStrictMode(): void
    {
        $baseUrl = 'baseUrl';
        $routeString = 'routeString';
        $requestMethod = 'GET';

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getBaseUrl()
            ->willReturn($baseUrl)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getPath()
            ->willReturn($routeString)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getMethod()
            ->willReturn($requestMethod)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getRequestFormat()
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint($this->serviceRequestProphecy->reveal())
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        // Body-less method in strict mode: the serializer must not be asked for a body at all.
        $this->serializerProphecy
            ->serialize(Argument::cetera())
            ->shouldNotBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($baseUrl . $routeString)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->requestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

        // No body => no stream created and no withBody() call.
        $this->streamFactoryProphecy
            ->createStream(Argument::any())
            ->shouldNotBeCalled()
        ;

        $requestProphecy
            ->withBody(Argument::any())
            ->shouldNotBeCalled()
        ;

        $this->requestVisitorRegistryProphecy
            ->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([])
            ->shouldBeCalled()
        ;

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            true
        );

        self::assertInstanceOf(
            RequestInterface::class,
            $requestBuilder->create($this->serviceRequestProphecy->reveal())
        );
    }

    /**
     * @dataProvider dateTimeQueryParamProvider
     *
     * @return void
     */
    public function testDateTimeQueryParamIsFormatted(?string $dateTimeFormat, string $expectedUri): void
    {
        $baseUrl = 'baseUrl';
        $routeString = '/rates?markup_date={markupDate}';
        $requestMethod = 'GET';
        $requestBody = '';

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getBaseUrl()->willReturn($baseUrl)->shouldBeCalled();
        $endpointProphecy->getPath()->willReturn($routeString)->shouldBeCalled();
        $endpointProphecy->getMethod()->willReturn($requestMethod)->shouldBeCalled();
        $endpointProphecy->getRequestFormat()->willReturn(EndpointInterface::FORMAT_JSON)->shouldBeCalled();
        $endpointProphecy->getDateTimeFormat()->willReturn($dateTimeFormat)->shouldBeCalled();
        $endpoint = $endpointProphecy->reveal();

        $uri = $this->prophesize(UriInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();
        $stream = $this->prophesize(StreamInterface::class)->reveal();

        $serviceRequest = $this->getMockBuilder(ServiceRequestInterface::class)
            ->addMethods(['getMarkupDate'])
            ->getMock();

        $serviceRequest
            ->expects($this->once())
            ->method('getMarkupDate')
            ->willReturn(new \DateTimeImmutable('2026-03-12T15:09:26+01:00'));

        $this->endpointRegistryProphecy->getEndpoint($serviceRequest)->willReturn($endpoint)->shouldBeCalled();
        $this->serializerProphecy
            ->serialize($serviceRequest, EndpointInterface::FORMAT_JSON)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;
        $this->uriFactoryProphecy->createUri($expectedUri)->willReturn($uri)->shouldBeCalled();
        $this->requestFactoryProphecy->createRequest($requestMethod, $uri)->willReturn($request)->shouldBeCalled();
        $this->streamFactoryProphecy->createStream($requestBody)->willReturn($stream)->shouldBeCalled();
        $requestProphecy->withBody($stream)->willReturn($request)->shouldBeCalled();
        $this->requestVisitorRegistryProphecy
            ->getRegisteredRequestVisitors(EndpointInterface::FORMAT_JSON)
            ->willReturn([])
            ->shouldBeCalled()
        ;
        $this->requestDecoratorProphecy->visit($request)->shouldNotBeCalled();

        $requestBuilder = new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            false
        );

        $this->assertInstanceOf(RequestInterface::class, $requestBuilder->create($serviceRequest));
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public function dateTimeQueryParamProvider(): array
    {
        return [
            'endpoint format is used' => ['Y-m-d', 'baseUrl/rates?markup_date=2026-03-12'],
            'falls back to ATOM when the endpoint declares none' => [
                null,
                'baseUrl/rates?markup_date=2026-03-12T15%3A09%3A26%2B01%3A00',
            ],
        ];
    }
}
