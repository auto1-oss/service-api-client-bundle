<?php

namespace Auto1\ServiceAPIClientBundle;

use Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass\ClientLoggerCompilerPass;
use Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass\RequestVisitorCompilerPass;
use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class Auto1ServiceAPIClientBundle
 */
class Auto1ServiceAPIClientBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->addCompilerPass(new ClientLoggerCompilerPass())
            ->addCompilerPass(new RequestVisitorCompilerPass());

        $container->registerForAutoconfiguration(ResponseTransformerStrategyInterface::class)
            ->addTag('response.transformer.strategies');
    }
}
