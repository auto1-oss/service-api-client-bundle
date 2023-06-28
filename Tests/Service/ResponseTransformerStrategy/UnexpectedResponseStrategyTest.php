<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\DeserializableResponseException;
use Auto1\ServiceAPIClientBundle\Service\Deserializer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy\UnexpectedResponseStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class UnexpectedResponseStrategyTest extends TestCase
{
    /**
     * @var Deserializer&MockObject
     */
    private $deserializer;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var UnexpectedResponseStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->deserializer = $this->createMock(Deserializer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new UnexpectedResponseStrategy($this->deserializer);
        $this->strategy->setLogger($this->logger);
    }

    /**
     * @dataProvider supportedResponses
     */
    public function testSupportsAllResponses(ResponseInterface $response): void
    {
        $this->assertTrue(
            $this->strategy->supports($response)
        );
    }

    public function supportedResponses(): array
    {
        return [
            [$this->createResponseWithStatus(100)],
            [$this->createResponseWithStatus(200)],
            [$this->createResponseWithStatus(300)],
            [$this->createResponseWithStatus(400)],
            [$this->createResponseWithStatus(500)],
        ];
    }

    public function testHandlesResponse(): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $response = $this->createResponseWithStatus($statusCode = 666);
        $responseBody = 'bodyyy';
        $errorMessage = 'Error response 666 cannot be deserialized';

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with($errorMessage);

        $this->expectExceptionObject(new DeserializableResponseException(
            $this->deserializer,
            new ErrorResponse(
                $endpoint,
                $errorMessage,
                $statusCode,
                $responseBody
            )
        ));

        $this->strategy->handle($endpoint, $response, $responseBody);
    }

    public function testLogsErrorWhenResponseIs504(): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $response = $this->createResponseWithStatus($statusCode = 504);
        $responseBody = 'bodyyy';
        $errorMessage = 'service request failed due to 504 gateway timeout';

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($errorMessage);

        $this->expectExceptionObject(new DeserializableResponseException(
            $this->deserializer,
            new ErrorResponse(
                $endpoint,
                $errorMessage,
                $statusCode,
                $responseBody
            )
        ));

        $this->strategy->handle($endpoint, $response, $responseBody);
    }

    public function testLogsErrorWhenResponseIs503(): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $response = $this->createResponseWithStatus($statusCode = 503);
        $responseBody = 'bodyyy';
        $errorMessage = 'service request failed due to 503 service unavailable';

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($errorMessage);

        $this->expectExceptionObject(new DeserializableResponseException(
            $this->deserializer,
            new ErrorResponse(
                $endpoint,
                $errorMessage,
                $statusCode,
                $responseBody
            )
        ));

        $this->strategy->handle($endpoint, $response, $responseBody);
    }

    public function testLogsErrorWhenResponseIs502(): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $response = $this->createResponseWithStatus($statusCode = 502);
        $responseBody = 'bodyyy';
        $errorMessage = 'service request failed due to 502 bad gateway';

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($errorMessage);

        $this->expectExceptionObject(new DeserializableResponseException(
            $this->deserializer,
            new ErrorResponse(
                $endpoint,
                $errorMessage,
                $statusCode,
                $responseBody
            )
        ));

        $this->strategy->handle($endpoint, $response, $responseBody);
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
