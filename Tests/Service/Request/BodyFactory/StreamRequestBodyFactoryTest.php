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

use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\StreamRequestBodyFactory;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\BodyFactory\Fixtures\StreamServiceRequest;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use PHPUnit\Framework\TestCase;

class StreamRequestBodyFactoryTest extends TestCase
{
    public function testSupportsReturnsTrueForAStreamRequest(): void
    {
        $request = $this->createMock(StreamServiceRequest::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $target = $this->getCut();

        $supports = $target->supports($request, $endpoint);

        self::assertTrue($supports);
    }

    public function testSupportsReturnsFalseForANonStreamRequest(): void
    {
        $request = $this->createMock(ServiceRequestInterface::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $target = $this->getCut();

        $supports = $target->supports($request, $endpoint);

        self::assertFalse($supports);
    }

    public function testSupportsReturnsFalseForAStreamRequestOnAMultipartEndpoint(): void
    {
        $request = $this->createMock(StreamServiceRequest::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getRequestFormat')->willReturn(EndpointInterface::FORMAT_MULTIPART);
        $target = $this->getCut();

        $supports = $target->supports($request, $endpoint);

        self::assertFalse($supports);
    }

    public function testCreateReturnsTheRequestStreamUnchanged(): void
    {
        $request = $this->createMock(StreamServiceRequest::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $target = $this->getCut();

        $body = $target->create($request, $endpoint);

        self::assertSame($request, $body);
    }

    private function getCut(): StreamRequestBodyFactory
    {
        return new StreamRequestBodyFactory();
    }
}