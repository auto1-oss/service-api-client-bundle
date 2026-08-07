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

namespace Auto1\ServiceAPIClientBundle\Tests\Service\Request\BodyFactory;

use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\SerializerRequestBodyFactory;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerRequestBodyFactoryTest extends TestCase
{
    private const TARGET_SERIALIZED_BODY = 'serialized-body';

    public function testSupportsReturnsTrueForEveryRequest(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(ServiceRequestInterface::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $target = $this->getCut($serializer);

        $supports = $target->supports($request, $endpoint);

        self::assertTrue($supports);
    }

    /**
     * @dataProvider formatProvider
     */
    public function testCreateSerializesWithTheEndpointRequestFormat(string $format): void
    {
        $request = $this->createMock(ServiceRequestInterface::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getRequestFormat')->willReturn($format);
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('serialize')
            ->with($request, $format)
            ->willReturn(self::TARGET_SERIALIZED_BODY);
        $target = $this->getCut($serializer);

        $body = $target->create($request, $endpoint);

        self::assertSame(self::TARGET_SERIALIZED_BODY, $body);
    }

    public function formatProvider(): array
    {
        return [
            'json' => ['json'],
            'url' => ['url'],
            'json-patch' => ['json-patch'],
        ];
    }

    private function getCut(SerializerInterface $serializer): SerializerRequestBodyFactory
    {
        return new SerializerRequestBodyFactory($serializer);
    }
}