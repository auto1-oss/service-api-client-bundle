<?php

namespace Auto1\ServiceAPIClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class Auto1ServiceAPIClientExtension
 */
class Auto1ServiceAPIClientExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('auto1_service_api_client.request_visitors', $config['request_visitors']);
        $container->setParameter('auto1_service_api_client.propagate_headers', $config['propagate_headers']);
        $container->setParameter('auto1_service_api_client.request_time_log_level', $config['request_time_log_level']);

        /*
         * Headers to propagate are by default taken from @request_stack if exists
         * so if there is no initial request - all related service definitions will be removed
         */
        if (!$container->hasDefinition('request_stack')) {
            $container->removeDefinition('auto1.api.request.visitor.header_propagation');
            $container->removeDefinition('auto1.api.previous_request');
        }
    }
}
