<?php

namespace AutoMapper\Bundle\DependencyInjection\Compiler;

use AutoMapper\Generator\Generator;
use AutoMapper\Transformer\ChainTransformerFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TransformerFactoryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $selectors = [];

        foreach ($this->findAndSortTaggedServices('automapper.transformer_factory', $container) as $definition) {
            $selectors[] = $definition;
        }

        $definition = $container->getDefinition(ChainTransformerFactory::class);

        if (class_exists(Generator::class)) {
            foreach ($selectors as $selector) {
                $definition->addMethodCall('addTransformerFactory', [$selector]);
            }
        } else {
            $definition->replaceArgument(0, $selectors);
        }
    }
}
