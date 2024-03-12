<?php

namespace AutoMapper\Bundle\DependencyInjection;

use AutoMapper\Bundle\AutoMapper;
use AutoMapper\Bundle\CacheWarmup\CacheWarmerLoaderInterface;
use AutoMapper\Bundle\CacheWarmup\ConfigurationCacheWarmerLoader;
use AutoMapper\Bundle\Configuration\MapperConfigurationInterface;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Generator\Generator;
use AutoMapper\Loader\FileLoader;
use AutoMapper\MapperGeneratorMetadataFactory;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\Normalizer\AutoMapperNormalizer;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Uid\AbstractUid;

class AutoMapperExtension extends Extension
{
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(MapperGeneratorMetadataInterface::class)->addTag('automapper.mapper_metadata');
        $container->registerForAutoconfiguration(MapperConfigurationInterface::class)->addTag('automapper.mapper_configuration');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if (class_exists(Generator::class)) {
            $loader->load('generator.xml');
            $loader->load('services.xml');
        } else {
            // AutoMapper 8.2
            $loader->load('mapper_generator.xml');
            $loader->load('custom_transformers.xml');
            $loader->load('services_82.xml');
        }

        $container->getDefinition(MapperGeneratorMetadataFactory::class)
            ->replaceArgument(5, $config['date_time_format'])
            ->replaceArgument(6, $config['map_private_properties'])
        ;

        $container->getDefinition(FileLoader::class)->replaceArgument(2, $config['hot_reload']);
        $container->registerForAutoconfiguration(TransformerFactoryInterface::class)->addTag('automapper.transformer_factory');

        if (class_exists(AbstractUid::class)) {
            $container
                ->getDefinition(SymfonyUidTransformerFactory::class)
                ->addTag('automapper.transformer_factory', ['priority' => '-1004']);
        }

        if ($config['normalizer']) {
            $container
                ->getDefinition(AutoMapperNormalizer::class)
                ->addTag('serializer.normalizer', ['priority' => 1000]);
        }

        if (null !== $config['name_converter']) {
            $container
                ->getDefinition(FromTargetMappingExtractor::class)
                ->addArgument(new Reference($config['name_converter']));

            $container
                ->getDefinition(FromSourceMappingExtractor::class)
                ->addArgument(new Reference($config['name_converter']));
        }

        if (class_exists(CustomTransformersRegistry::class)) {
            $autoMapperDefinition = $container->getDefinition(AutoMapper::class);

            $mapperDefinition = $autoMapperDefinition->getArgument(2);

            $autoMapperDefinition->replaceArgument(2, new Reference(CustomTransformersRegistry::class));
            $autoMapperDefinition->addArgument($mapperDefinition);
        }

        if (class_exists(Generator::class) && $config['allow_readonly_target_to_populate']) {
            $container
                ->getDefinition(Generator::class)
                ->replaceArgument(2, $config['allow_readonly_target_to_populate']);
        }

        $container->setParameter('automapper.cache_dir', $config['cache_dir']);

        $container->registerForAutoconfiguration(CacheWarmerLoaderInterface::class)->addTag('automapper.cache_warmer_loader');
        $container
            ->getDefinition(ConfigurationCacheWarmerLoader::class)
            ->replaceArgument(0, $config['warmup']);
    }

    public function getAlias(): string
    {
        return 'automapper';
    }
}
