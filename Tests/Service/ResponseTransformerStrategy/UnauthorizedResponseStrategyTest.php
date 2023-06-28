<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotAuthorizedException;
use Auto1\ServiceAPIClientBundle\Service\Deserializer;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy\UnauthorizedResponseStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class UnauthorizedResponseStrategyTest extends TestCase
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
     * @var UnauthorizedResponseStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->deserializer = $this->createMock(Deserializer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new UnauthorizedResponseStrategy($this->deserializer);
        $this->strategy->setLogger($this->logger);
    }

    public function testHandlesUnauthorizedResponses(): void
    {
        $this->assertTrue(
            $this->strategy->supports(
                $this->createResponseWithStatus(401)
            )
        );
    }

    /**
     * @dataProvider unsupportedResponses
     */
    public function testDoesntSupportOtherResponses(ResponseInterface $unsupportedResponse): void
    {
        $this->assertFalse(
            $this->strategy->supports($unsupportedResponse)
        );
    }

    public function unsupportedResponses(): array
    {
        return [
            [$this->createResponseWithStatus(100)],
            [$this->createResponseWithStatus(200)],
            [$this->createResponseWithStatus(300)],
            [$this->createResponseWithStatus(400)],
            [$this->createResponseWithStatus(402)],
            [$this->createResponseWithStatus(500)],
        ];
    }

    public function testHandlesUnauthorizedResponse(): void
    {
        $responseBody = 'some response';
        $errorResponse = new ErrorResponse();
        $errorResponse->setMessage($errorMessage = 'error message');

        $endpoint = $this->createMock(EndpointInterface::class);

        $this->deserializer
            ->expects(self::once())
            ->method('deserialize')
            ->with($endpoint, ErrorResponse::class, $responseBody)
            ->willReturn($errorResponse);

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with($errorMessage);

        $this->expectException(NotAuthorizedException::class);

        $this->strategy->handle($endpoint, $this->createResponseWithStatus(401), $responseBody);
    }

    public function testHandlingFailsOnDeserializationFailure(): void
    {
        $this->deserializer
            ->expects(self::once())
            ->method('deserialize')
            ->willThrowException($expectedException = new MalformedResponseException());

        $this->logger
            ->expects(self::never())
            ->method('debug');

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->expectExceptionObject($expectedException);

        $this->strategy->handle(
            $this->createMock(EndpointInterface::class),
            $this->createMock(ResponseInterface::class),
            'response body'
        );
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
