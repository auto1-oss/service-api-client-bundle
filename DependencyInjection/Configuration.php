<?php

namespace Auto1\ServiceAPIClientBundle\DependencyInjection;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
    const KEY_SERVICE = 'service';
    const KEY_FORMAT = 'format';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('auto1_service_api_client');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('request_time_log_level')->defaultValue(LogLevel::DEBUG)->end()
            ->end()
            ->children()
                ->arrayNode('request_visitors')
                    ->defaultValue([
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.header_propagation',
                            self::KEY_FORMAT => null,
                        ],
                        //TODO: separate configuration headers\formats, those are immutable
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.content_type.file',
                            self::KEY_FORMAT => 'stream',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.content_type.url',
                            self::KEY_FORMAT => 'url',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.content_type.json',
                            self::KEY_FORMAT => 'json',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.content_type.json_patch',
                            self::KEY_FORMAT => 'json-patch',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.accept.url',
                            self::KEY_FORMAT => 'url',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.accept.json',
                            self::KEY_FORMAT => 'json',
                        ],
                        [
                            self::KEY_SERVICE => 'auto1.api.request.visitor.accept.json_patch',
                            self::KEY_FORMAT => 'json-patch',
                        ],
                    ])
                    ->arrayPrototype()
                        ->canBeUnset()
                        ->treatNullLike(false)
                        ->children()
                            ->scalarNode(self::KEY_SERVICE)->isRequired()->end()
                            ->scalarNode(self::KEY_FORMAT)->defaultValue(null)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('propagate_headers')
                    ->defaultValue([])->arrayPrototype()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
