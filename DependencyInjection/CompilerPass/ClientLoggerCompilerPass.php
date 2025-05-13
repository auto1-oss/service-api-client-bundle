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

use Auto1\ServiceAPIClientBundle\Service\ClientLogger\PsrClientLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientLoggerCompilerPass implements CompilerPassInterface
{
    const REGISTRY = 'auto1.api.client_logger.registry';
    const METHOD_REGISTER_LOGGER = 'registerLogger';
    const TAG = 'auto1.api.client_logger';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $enableDefaultLogger = $container->getParameter('auto1_service_api_client.enable_default_logger');

        $registryDefinition = $container->getDefinition(self::REGISTRY);
        $governedDefinitions = $container->findTaggedServiceIds(self::TAG);

        foreach ($governedDefinitions as $serviceId => $tags) {
            $loggerDefinition = $container->getDefinition($serviceId);

            if (!$enableDefaultLogger && $loggerDefinition->getClass() === PsrClientLogger::class) {
                continue;
            }

            $registryDefinition->addMethodCall(
                self::METHOD_REGISTER_LOGGER,
                [$loggerDefinition],
            );
        }
    }
}
