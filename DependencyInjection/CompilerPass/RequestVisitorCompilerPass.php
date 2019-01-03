<?php

namespace Auto1\ServiceAPIClientBundle\DependencyInjection\CompilerPass;

use Auto1\ServiceAPIComponentsBundle\Exception\Core\ConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Auto1\ServiceAPIClientBundle\DependencyInjection\Configuration;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\RequestVisitorInterface;

/**
 * Class RequestVisitorCompilerPass
 */
class RequestVisitorCompilerPass implements CompilerPassInterface
{
    const VISITOR_TAG_NAME = 'auto1.api.request_visitor';
    const VISITOR_TAG_KEY_FORMAT = 'format';
    const METHOD_REGISTER_VISITOR = 'registerRequestVisitor';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $requiredVisitors = $container->getParameter('auto1_service_api_client.request_visitors');
        $requestVisitorRegistryDefinition = $container->getDefinition('auto1.api.request.visitor.registry');
        $appliedVisitors = [];

        foreach ($requiredVisitors as $visitor) {
            $requestVisitorRegistryDefinition->addMethodCall(
                self::METHOD_REGISTER_VISITOR,
                [
                    $container->getDefinition($visitor[Configuration::KEY_SERVICE]),
                    $visitor[Configuration::KEY_FORMAT],
                ]
            );

            $appliedVisitors[] = $visitor[Configuration::KEY_SERVICE];
        }

        $visitorDefinitions = $container->findTaggedServiceIds(self::VISITOR_TAG_NAME);

        foreach ($visitorDefinitions as $id => $tags) {
            if (\in_array($id, $appliedVisitors)) {
                continue;
            }

            if (!is_subclass_of($container->getDefinition($id)->getClass(), RequestVisitorInterface::class, true)) {
                throw new ConfigurationException(
                    sprintf('%s should be instance of %s', self::VISITOR_TAG_NAME, RequestVisitorInterface::class)
                );
            }

            //Service declaration can be tagged multiple times with the same tag
            foreach ($tags as $tag) {
                $requestVisitorRegistryDefinition->addMethodCall(
                    self::METHOD_REGISTER_VISITOR,
                    [$container->getDefinition($id), $tag[self::VISITOR_TAG_KEY_FORMAT] ?? null]
                );
            }
        }
    }
}
