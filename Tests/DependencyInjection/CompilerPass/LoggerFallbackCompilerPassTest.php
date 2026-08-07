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

namespace Auto1\ServiceAPIClientBundle\Tests\DependencyInjection\CompilerPass;

use Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass\LoggerFallbackCompilerPass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LoggerFallbackCompilerPassTest extends TestCase
{
    private const TARGET_ALIAS = 'auto1.api.logger';
    private const TARGET_NULL_LOGGER_ID = 'auto1.api.logger.null';
    private const TARGET_LOGGER_ID = 'logger';
    private const TARGET_APP_LOGGER_ID = 'app.custom_logger';

    public function testProcessRepointsTheAliasToTheApplicationLoggerWhenPresent(): void
    {
        $container = $this->containerWithDefaultAlias();
        $loggerDefinition = new Definition(LoggerInterface::class);
        $container->setDefinition(self::TARGET_LOGGER_ID, $loggerDefinition);
        $target = $this->getCut();

        $target->process($container);

        $alias = (string) $container->getAlias(self::TARGET_ALIAS);
        self::assertSame(self::TARGET_LOGGER_ID, $alias);
    }

    public function testProcessKeepsTheNullLoggerFallbackWhenNoApplicationLoggerExists(): void
    {
        $container = $this->containerWithDefaultAlias();
        $target = $this->getCut();

        $target->process($container);

        $alias = (string) $container->getAlias(self::TARGET_ALIAS);
        self::assertSame(self::TARGET_NULL_LOGGER_ID, $alias);
    }

    public function testProcessKeepsAnApplicationOverrideOfTheAlias(): void
    {
        $container = $this->containerWithDefaultAlias();
        $loggerDefinition = new Definition(LoggerInterface::class);
        $container->setDefinition(self::TARGET_LOGGER_ID, $loggerDefinition);
        $appLoggerDefinition = new Definition(LoggerInterface::class);
        $container->setDefinition(self::TARGET_APP_LOGGER_ID, $appLoggerDefinition);
        $container->setAlias(self::TARGET_ALIAS, self::TARGET_APP_LOGGER_ID);
        $target = $this->getCut();

        $target->process($container);

        $alias = (string) $container->getAlias(self::TARGET_ALIAS);
        self::assertSame(self::TARGET_APP_LOGGER_ID, $alias);
    }

    public function testProcessKeepsAnApplicationRedefinitionOfTheServiceId(): void
    {
        $container = $this->containerWithDefaultAlias();
        $loggerDefinition = new Definition(LoggerInterface::class);
        $container->setDefinition(self::TARGET_LOGGER_ID, $loggerDefinition);
        $appLoggerDefinition = new Definition(LoggerInterface::class);
        $container->removeAlias(self::TARGET_ALIAS);
        $container->setDefinition(self::TARGET_ALIAS, $appLoggerDefinition);
        $target = $this->getCut();

        $target->process($container);

        self::assertFalse($container->hasAlias(self::TARGET_ALIAS));
        $definition = $container->getDefinition(self::TARGET_ALIAS);
        self::assertSame($appLoggerDefinition, $definition);
    }

    private function getCut(): LoggerFallbackCompilerPass
    {
        return new LoggerFallbackCompilerPass();
    }

    /**
     * Mirrors the default wiring from services.yml.
     */
    private function containerWithDefaultAlias(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $nullLoggerDefinition = new Definition(NullLogger::class);
        $container->setDefinition(self::TARGET_NULL_LOGGER_ID, $nullLoggerDefinition);
        $container->setAlias(self::TARGET_ALIAS, self::TARGET_NULL_LOGGER_ID);

        return $container;
    }
}