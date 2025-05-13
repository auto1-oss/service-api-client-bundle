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

namespace Auto1\ServiceAPIClientBundle\Tests\Service\ResponseTransformerStrategy;

use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotFoundException;
use Auto1\ServiceAPIClientBundle\Service\DeserializerInterface;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy\NotFoundResponseStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class NotFoundResponseStrategyTest extends TestCase
{
    /**
     * @var DeserializerInterface&MockObject
     */
    private $deserializer;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var NotFoundResponseStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->deserializer = $this->createMock(DeserializerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new NotFoundResponseStrategy($this->deserializer);
        $this->strategy->setLogger($this->logger);
    }

    public function testSupportsNotFoundResponses(): void
    {
        $this->assertTrue(
            $this->strategy->supports(
                $this->createResponseWithStatus(404)
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
            [$this->createResponseWithStatus(403)],
            [$this->createResponseWithStatus(405)],
            [$this->createResponseWithStatus(500)],
        ];
    }

    /**
     * @dataProvider possibleValidScenarios
     */
    public function testHandlingResponses(string $responseBody, ?string $responseClass, string $expectedMessage): void
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint
            ->method('getResponseClass')
            ->willReturn($responseClass);
        $endpoint
            ->method('getMethod')
            ->willReturn('KOZA');
        $endpoint
            ->method('getBaseUrl')
            ->willReturn('mzlj://kopytkowo.pl');
        $endpoint
            ->method('getPath')
            ->willReturn('/v2/kopytka/niosa/mnie');

        $this->deserializer
            ->expects('' === $responseBody ? self::never() : self::once())
            ->method('deserialize')
            ->with($endpoint, ErrorResponse::class, $responseBody)
            ->willReturn(new ErrorResponse());

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with($expectedMessage);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->strategy->handle($endpoint, $this->createResponseWithStatus(404), $responseBody);
    }

    public function possibleValidScenarios(): array
    {
        return [
            // response body, response class, expected message
            ['some body', 'SomeClass', 'SomeClass not found'],
            ['', null, 'KOZA mzlj://kopytkowo.pl/v2/kopytka/niosa/mnie returned not found'],
        ];
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
