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

use Auto1\ServiceAPIClientBundle\Exception\Response\ServiceUnavailableResponseException;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy\ServiceUnavailableResponseStrategy;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ServiceUnavailableResponseStrategyTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private  $logger;

    /**
     * @var ServiceUnavailableResponseStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->strategy = new ServiceUnavailableResponseStrategy();
        $this->strategy->setLogger($this->logger);
    }

    public function testSupportsBadGatewayResponse(): void
    {
        $this->assertTrue(
            $this->strategy->supports(
                $this->createResponseWithStatus(503)
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

    public function testHandlesBadGatewayResponse(): void
    {
        $responseBody = 'some response';
        $errorMessage = 'service request failed due to 503 service unavailable';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(504);

        $endpoint = $this->createMock(EndpointInterface::class);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(ServiceUnavailableResponseException::class);

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
