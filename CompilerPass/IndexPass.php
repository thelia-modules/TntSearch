<?php

namespace TntSearch\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IndexPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('tntsearch.indexation.provider')) {
            return;
        }

        $definition = $container->findDefinition('tntsearch.indexation.provider');

        $taggedServices = $container->findTaggedServiceIds('tntsearch.index');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addIndex', [new Reference($id)]);
        }
    }
}