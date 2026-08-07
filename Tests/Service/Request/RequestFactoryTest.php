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
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\RequestBodyFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\RequestBodyFactoryRegistryInterface;
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
     * @var RequestBodyFactoryRegistryInterface|ObjectProphecy
     */
    private $requestBodyFactoryRegistryProphecy;

    /**
     * @var RequestBodyFactoryInterface|ObjectProphecy
     */
    private $requestBodyFactoryProphecy;

    /**
     * @var RequestVisitorRegistryInterface|ObjectProphecy
     */
    private $requestVisitorRegistryProphecy;

    /**
     * @var RequestVisitorInterface|ObjectProphecy
     */
    private $requestDecoratorProphecy;

    /**
     * @var UriFactoryInterface|ObjectProphecy
     */
    private $uriFactoryProphecy;

    /**
     * @var PsrRequestFactoryInterface|ObjectProphecy
     */
    private $httpRequestFactoryProphecy;

    /**
     * @var StreamFactoryInterface|ObjectProphecy
     */
    private $streamFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serviceRequestProphecy = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy = $this->prophesize(EndpointRegistryInterface::class);
        $this->requestBodyFactoryRegistryProphecy = $this->prophesize(RequestBodyFactoryRegistryInterface::class);
        $this->requestBodyFactoryProphecy = $this->prophesize(RequestBodyFactoryInterface::class);
        $this->requestVisitorRegistryProphecy = $this->prophesize(RequestVisitorRegistryInterface::class);
        $this->requestDecoratorProphecy = $this->prophesize(RequestVisitorInterface::class);
        $this->uriFactoryProphecy = $this->prophesize(UriFactoryInterface::class);
        $this->httpRequestFactoryProphecy = $this->prophesize(PsrRequestFactoryInterface::class);
        $this->streamFactoryProphecy = $this->prophesize(StreamFactoryInterface::class);
    }

    /**
     * @return RequestFactory
     */
    private function getCut(bool $strictModeEnabled = false): RequestFactory
    {
        return new RequestFactory(
            $this->endpointRegistryProphecy->reveal(),
            $this->requestBodyFactoryRegistryProphecy->reveal(),
            $this->requestVisitorRegistryProphecy->reveal(),
            $this->uriFactoryProphecy->reveal(),
            $this->httpRequestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $strictModeEnabled
        );
    }

    /**
     * @return void
     */
    public function testBuildFlow()
    {
        $serviceRequest = $this->serviceRequestProphecy->reveal();
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
        $stream = $this->prophesize(StreamInterface::class)->reveal();
        $requestProphecy = $this->prophesize(RequestInterface::class);
        $request = $requestProphecy->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->requestBodyFactoryProphecy
            ->create($serviceRequest, $endpoint)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;
        $this->requestBodyFactoryRegistryProphecy
            ->getFactory($serviceRequest, $endpoint)
            ->willReturn($this->requestBodyFactoryProphecy->reveal())
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($baseUrl . $routeString)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->httpRequestFactoryProphecy
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

        $target = $this->getCut();

        $httpRequest = $target->create($serviceRequest);

        self::assertInstanceOf(RequestInterface::class, $httpRequest);
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
        $request = $this->prophesize(RequestInterface::class)->reveal();

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

        $this->requestBodyFactoryProphecy
            ->create($serviceRequest, $endpoint)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;
        $this->requestBodyFactoryRegistryProphecy
            ->getFactory($serviceRequest, $endpoint)
            ->willReturn($this->requestBodyFactoryProphecy->reveal())
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($expectedUri)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        // empty body -> no stream wrapping
        $this->httpRequestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;
        $this->streamFactoryProphecy
            ->createStream(Argument::any())
            ->shouldNotBeCalled()
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

        $target = $this->getCut();

        $httpRequest = $target->create($serviceRequest);

        self::assertInstanceOf(RequestInterface::class, $httpRequest);
    }

    /**
     * @return void
     */
    public function testBuildFlowValidationFailsOnUnmappedRequestArguments()
    {
        $serviceRequest = $this->serviceRequestProphecy->reveal();
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
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $target = $this->getCut();

        $this->expectException(InvalidArgumentException::class);

        $target->create($serviceRequest);
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
        $request = $this->prophesize(RequestInterface::class)->reveal();

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

        $this->requestBodyFactoryProphecy
            ->create($serviceRequest, $endpoint)
            ->willReturn($requestBody)
            ->shouldBeCalled()
        ;
        $this->requestBodyFactoryRegistryProphecy
            ->getFactory($serviceRequest, $endpoint)
            ->willReturn($this->requestBodyFactoryProphecy->reveal())
            ->shouldBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($expectedUri)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->httpRequestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;
        $this->streamFactoryProphecy
            ->createStream(Argument::any())
            ->shouldNotBeCalled()
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

        $target = $this->getCut();

        $httpRequest = $target->create($serviceRequest);

        self::assertInstanceOf(RequestInterface::class, $httpRequest);
    }

    public function testBuildFlowSkipsBodyForBodilessMethodInStrictMode(): void
    {
        $serviceRequest = $this->serviceRequestProphecy->reveal();
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
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        // Body-less method in strict mode: no body factory is resolved at all.
        $this->requestBodyFactoryRegistryProphecy
            ->getFactory(Argument::cetera())
            ->shouldNotBeCalled()
        ;

        $this->uriFactoryProphecy
            ->createUri($baseUrl . $routeString)
            ->willReturn($uri)
            ->shouldBeCalled()
        ;

        $this->httpRequestFactoryProphecy
            ->createRequest($requestMethod, $uri)
            ->willReturn($request)
            ->shouldBeCalled()
        ;

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

        $target = $this->getCut(true);

        $httpRequest = $target->create($serviceRequest);

        self::assertInstanceOf(RequestInterface::class, $httpRequest);
    }
}
