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

use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\RequestBodyFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Request\BodyFactory\RequestBodyFactoryRegistry;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

class RequestBodyFactoryRegistryTest extends TestCase
{
    public function testGetFactoryReturnsTheFirstSupportingFactory(): void
    {
        $request = $this->createMock(ServiceRequestInterface::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $skipped = $this->factory(false);
        $skipped->expects(self::never())->method('create');
        $expected = $this->factory(true);
        $alsoSupporting = $this->factory(true);
        $target = $this->getCut([$skipped, $expected, $alsoSupporting]);

        $actual = $target->getFactory($request, $endpoint);

        self::assertSame($expected, $actual);
    }

    public function testGetFactoryThrowsWhenNoFactorySupportsTheRequest(): void
    {
        $request = $this->createMock(ServiceRequestInterface::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getRequestFormat')->willReturn('unknown');
        $unsupporting = $this->factory(false);
        $target = $this->getCut([$unsupporting]);

        $this->expectException(LogicException::class);

        $target->getFactory($request, $endpoint);
    }

    /**
     * @param RequestBodyFactoryInterface[] $factories
     */
    private function getCut(array $factories): RequestBodyFactoryRegistry
    {
        return new RequestBodyFactoryRegistry($factories);
    }

    private function factory(bool $supports): RequestBodyFactoryInterface
    {
        $factory = $this->createMock(RequestBodyFactoryInterface::class);
        $factory->method('supports')->willReturn($supports);

        return $factory;
    }
}