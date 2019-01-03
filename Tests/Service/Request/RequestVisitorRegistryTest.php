<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestVisitorRegistry;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\RequestVisitorInterface;

/**
 * Class RequestVisitorRegistryTest.
 */
class RequestVisitorRegistryTest extends TestCase
{
    const FORMAT1 = 'format1';
    const FORMAT2 = 'format2';

    /**
     * @dataProvider getDataForTestRequestVisitorRegistry
     *
     * @param array       $callArgumentsList
     * @param string|null $format
     * @param array       $expected
     */
    public function testRequestVisitorRegistry(
        array $callArgumentsList,
        string $format = null,
        array $expected
    ) {
        $requestVisitorRegistry = new RequestVisitorRegistry();
        foreach ($callArgumentsList as $callArguments) {
            $invokedMethod = new \ReflectionMethod(RequestVisitorRegistry::class, 'registerRequestVisitor');
            $invokedMethod->invokeArgs($requestVisitorRegistry, $callArguments);
        }

        self::assertSame(
            $expected,
            $requestVisitorRegistry->getRegisteredRequestVisitors($format)
        );
    }

    /**
     * @return array
     */
    public function getDataForTestRequestVisitorRegistry(): array
    {
        $requestVisitor1 = $this->prophesize(RequestVisitorInterface::class);
        $requestVisitor2 = $this->prophesize(RequestVisitorInterface::class);

        return [
            [
                [
                    [$requestVisitor1->reveal()],
                ],
                self::FORMAT1,
                [
                    $requestVisitor1->reveal(),
                ],
            ],
            [
                [
                    [$requestVisitor1->reveal(), self::FORMAT1],
                ],
                self::FORMAT1,
                [
                    $requestVisitor1->reveal(),
                ],
            ],
            [
                [
                    [$requestVisitor1->reveal(), self::FORMAT1],
                ],
                self::FORMAT2,
                [],
            ],
            [
                [
                    [$requestVisitor1->reveal(), self::FORMAT1],
                    [$requestVisitor2->reveal(), self::FORMAT2],
                ],
                self::FORMAT2,
                [
                    $requestVisitor2->reveal(),
                ],
            ],
            [
                [
                    [$requestVisitor1->reveal(), self::FORMAT1],
                    [$requestVisitor2->reveal()],
                ],
                self::FORMAT1,
                [
                    $requestVisitor1->reveal(),
                    $requestVisitor2->reveal(),
                ],
            ],
        ];
    }
}
