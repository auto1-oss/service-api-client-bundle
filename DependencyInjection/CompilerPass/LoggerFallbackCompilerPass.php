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

namespace Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Points the bundle-local `auto1.api.logger` alias at the application's `logger`
 * service when it exists (e.g. MonologBundle). Otherwise the alias keeps its
 * default target — a bundle-local NullLogger — so the bundle still compiles in an
 * application that provides no logger.
 *
 * Only the bundle's own NullLogger default is replaced: an application that
 * re-points the alias (or redefines the service id) keeps its override.
 */
class LoggerFallbackCompilerPass implements CompilerPassInterface
{
    const ALIAS = 'auto1.api.logger';
    const LOGGER = 'logger';
    const NULL_LOGGER = 'auto1.api.logger.null';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::LOGGER)) {
            return;
        }

        if (!$container->hasAlias(self::ALIAS)) {
            return;
        }

        if (self::NULL_LOGGER !== (string) $container->getAlias(self::ALIAS)) {
            return;
        }

        $container->setAlias(self::ALIAS, self::LOGGER);
    }
}
