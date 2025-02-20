<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass;

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
        $registryDefinition = $container->getDefinition(self::REGISTRY);
        $governedDefinitions = $container->findTaggedServiceIds(self::TAG);

        foreach ($governedDefinitions as $serviceId => $tags) {
            $registryDefinition->addMethodCall(
                self::METHOD_REGISTER_LOGGER,
                [$container->getDefinition($serviceId)]
            );
        }
    }
}
