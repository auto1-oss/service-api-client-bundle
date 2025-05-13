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

use Auto1\ServiceAPIClientBundle\Service\APIClient;
use Auto1\ServiceAPIClientBundle\Service\ClientLoggerRegistry;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\RequestTimer;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class APIClientTest.
 */
class APIClientTest extends TestCase
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
     * @var HttpClient|ObjectProphecy
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
        $this->clientProphecy = $this->prophesize(HttpClient::class);
    }

    public function testSend()
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
            ->willReturn($duration = 123)
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

        $this->clientProphecy
            ->sendRequest($request)
            ->willReturn($response)
            ->shouldBeCalled()
        ;

        $this->responseTransformerProphecy
            ->transform($response, $serviceRequest)
            ->willReturn($object)
            ->shouldBeCalled()
        ;

        $apiClient = new APIClient(
            $this->requestTimer->reveal(),
            $this->clientLoggerRegistry->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->responseTransformerProphecy->reveal(),
            $this->clientProphecy->reveal()
        );

        $result = $apiClient->send($serviceRequest);
        self::assertSame($object, $result);
    }
}
