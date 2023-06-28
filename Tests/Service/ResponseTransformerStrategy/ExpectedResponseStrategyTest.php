<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\Service\Deserializer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy\ExpectedResponseStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ExpectedResponseStrategyTest extends TestCase
{
    /**
     * @var Deserializer&MockObject
     */
    private $deserializer;

    /**
     * @var ExpectedResponseStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->deserializer = $this->createMock(Deserializer::class);
        $this->strategy = new ExpectedResponseStrategy($this->deserializer);
    }

    /**
     * @dataProvider supportedResponses
     */
    public function testSupportsSuccessfulResponses(ResponseInterface $response): void
    {
        $this->assertTrue($this->strategy->supports($response));
    }

    public function supportedResponses(): array
    {
        return [
            [$this->createResponseWithStatus(200)],
            [$this->createResponseWithStatus(250)],
            [$this->createResponseWithStatus(299)],
        ];
    }

    /**
     * @dataProvider unsupportedResponses
     */
    public function testDosNotSupportUnsuccessfulResponses(ResponseInterface $response): void
    {
        $this->assertFalse($this->strategy->supports($response));
    }

    public function unsupportedResponses(): array
    {
        return [
            [$this->createResponseWithStatus(100)],
            [$this->createResponseWithStatus(199)],
            [$this->createResponseWithStatus(300)],
            [$this->createResponseWithStatus(400)],
            [$this->createResponseWithStatus(500)],
        ];
    }

    public function testHandlesRawResponseAsResponseFormat(): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint
            ->method('getResponseClass')
            ->willReturn(null);

        $response = $this->strategy->handle(
            $endpoint,
            $this->createMock(ResponseInterface::class),
            $expectedResponse = 'this is some body',
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandlesSingleObjectResponseAsResponseFormat(): void
    {
        $body = 'this is some body';
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint
            ->method('getResponseClass')
            ->willReturn($className = 'SomeClass');

        $this->deserializer
            ->expects(self::once())
            ->method('deserialize')
            ->with($endpoint, $className, $body)
            ->willReturn($deserializedResponse = new \stdClass());

        $this->deserializer
            ->expects(self::never())
            ->method('deserializeAsArray');

        $response = $this->strategy->handle(
            $endpoint,
            $this->createMock(ResponseInterface::class),
            $body,
        );

        $this->assertEquals($deserializedResponse, $response);
    }

    public function testHandlesArrayOfObjectsResponseAsResponseFormat(): void
    {
        $body = 'this is some body';
        $className = 'SomeClass';
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint
            ->method('getResponseClass')
            ->willReturn($className . '[]');

        $this->deserializer
            ->expects(self::once())
            ->method('deserializeAsArray')
            ->with($endpoint, $className, $body)
            ->willReturn($deserializedResponse = [new \stdClass(), new \stdClass()]);

        $this->deserializer
            ->expects(self::never())
            ->method('deserialize');

        $response = $this->strategy->handle(
            $endpoint,
            $this->createMock(ResponseInterface::class),
            $body,
        );

        $this->assertEquals($deserializedResponse, $response);
    }

    private function createResponseWithStatus(int $statusCode): ResponseInterface
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock
            ->method('getStatusCode')
            ->willReturn($statusCode);

        return $mock;
    }
}
