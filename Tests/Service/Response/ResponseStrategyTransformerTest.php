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

use Auto1\ServiceAPIClientBundle\Service\Response\ResponseStrategyTransformer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Exception\Core\ConfigurationException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseStrategyTransformerTest extends TestCase
{
    /** @var ServiceRequestInterface|MockObject $request */
    private $request;

    /** @var ResponseInterface|MockObject $response */
    private $response;

    /** @var EndpointRegistryInterface|MockObject $endpointRegistry */
    private $endpointRegistry;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServiceRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->response
            ->method('getBody')
            ->willReturn($bodyStream = $this->createMock(StreamInterface::class));
        $bodyStream
            ->method('getContents')
            ->willReturn('this is a body');

        $this->endpointRegistry = $this->createMock(EndpointRegistryInterface::class);
        $this->endpointRegistry
            ->method('getEndpoint')
            ->with($this->request)
            ->willReturn($this->createMock(EndpointInterface::class));
    }

    public function testUsesOnlyFirstStrategySupportingTheResponse(): void
    {
        $responseTransformer = new ResponseStrategyTransformer($this->endpointRegistry, [
            $this->createStrategy(false),
            $this->createStrategy(true, true),
            $this->createStrategy(true, false, false),
        ]);

        $responseTransformer->transform($this->response, $this->request);
    }

    /**
     * @dataProvider strategiesNotSupportingPassedRequest
     */
    public function testThrowsExceptionWhenNoStrategySupportsTheResponse(
        ResponseTransformerStrategyInterface ...$strategies
    ): void {
        $responseTransformer = new ResponseStrategyTransformer($this->endpointRegistry, $strategies);

        $this->expectException(ConfigurationException::class);

        $responseTransformer->transform($this->response, $this->request);
    }

    public function strategiesNotSupportingPassedRequest(): array
    {
        return [
            [],
            [$this->createStrategy(false), $this->createStrategy(false)],
        ];
    }

    /**
     * @description Since body is a stream - each time it's fetched, the stream gets emptied and subsequent calls return
     * only data added since last call. In our case - that would yield empty responses.
     */
    public function testFetchesTheResponseBodyOnlyOnce(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($bodyStream = $this->createMock(StreamInterface::class));
        $bodyStream
            ->expects(self::once())
            ->method('getContents')
            ->willReturn('this is a body');

        $responseTransformer = new ResponseStrategyTransformer($this->endpointRegistry, [
            $this->createStrategy(false),
            $this->createStrategy(true, true),
        ]);

        $responseTransformer->transform($response, $this->request);
    }

    public function testReturnsResultFromUsedStrategy(): void
    {

        $expectedResult = 'koza tu byla';

        $responseTransformer = new ResponseStrategyTransformer(
            $this->endpointRegistry,
            [
                $this->createStrategy(
                    true,
                    true,
                    true,
                    $expectedResult
                ),
            ]
        );

        $result = $responseTransformer->transform($this->response, $this->request);

        $this->assertEquals($expectedResult, $result);
    }

    private function createStrategy(
        bool $supports = true,
        bool $shouldHandleBeCalled = false,
        bool $shouldSupportsBeCalled = true,
        $response = ''
    ): ResponseTransformerStrategyInterface {
        $strategy = $this->createMock(ResponseTransformerStrategyInterface::class);
        $strategy
            ->expects($shouldSupportsBeCalled ? self::once() : self::never())
            ->method('supports')
            ->willReturn($supports);

        $strategy
            ->expects($shouldHandleBeCalled ? self::once() : self::never())
            ->method('handle')
            ->willReturn($response);

        return $strategy;
    }
}
