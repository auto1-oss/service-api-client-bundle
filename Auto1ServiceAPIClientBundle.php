<?php

namespace Auto1\ServiceAPIClientBundle;

use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass\RequestVisitorCompilerPass;

/**
 * Class Auto1ServiceAPIClientBundle
 */
class Auto1ServiceAPIClientBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RequestVisitorCompilerPass());

        $container->registerForAutoconfiguration(ResponseTransformerStrategyInterface::class)
            ->addTag('response.transformer.strategies');
    }
}
