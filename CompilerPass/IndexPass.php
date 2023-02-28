<?php

namespace TntSearch\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TntSearch\Service\Provider\IndexationProvider;

class IndexPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(IndexationProvider::class)) {
            return;
        }

        $definition = $container->findDefinition(IndexationProvider::class);

        $taggedServices = $container->findTaggedServiceIds('tntsearch.index');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addIndex', [new Reference($id)]);
        }
    }
}