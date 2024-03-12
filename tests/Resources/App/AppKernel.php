<?php

namespace DummyApp;

require_once __DIR__ . '/Transformer/MoneyTransformerFactory.php';
require_once __DIR__ . '/Transformer/ArrayToMoneyTransformer.php';
require_once __DIR__ . '/Transformer/MoneyToArrayTransformer.php';
require_once __DIR__ . '/Transformer/MoneyToMoneyTransformer.php';

use AutoMapper\Bundle\AutoMapperBundle;
use AutoMapper\Bundle\Configuration\MapperConfigurationInterface;
use AutoMapper\Bundle\Tests\Fixtures\User;
use AutoMapper\Bundle\Tests\Fixtures\UserDTO;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new AutoMapperBundle(),
        ];

        return $bundles;
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $route = new Route('/', ['_controller' => 'kernel::indexAction']);
        $routes->collection->add('index_action', $route);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config.yml');
    }

    public function indexAction(): Response
    {
        return new Response();
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }
}

if (interface_exists(CustomPropertyTransformerInterface::class)) {
    class YearOfBirthTransformer implements CustomPropertyTransformerInterface
    {
        public function transform(object|array $user): mixed
        {
            assert($user instanceof User);

            return ((int) date('Y')) - ((int) $user->age);
        }

        public function supports(string $source, string $target, string $propertyName): bool
        {
            return User::class === $source && UserDTO::class === $target && 'yearOfBirth' === $propertyName;
        }
    }

    class UserMapperConfiguration
    {

    }
} else {
    class UserMapperConfiguration implements MapperConfigurationInterface
    {
        public function getSource(): string
        {
            return User::class;
        }

        public function getTarget(): string
        {
            return UserDTO::class;
        }

        public function process(MapperGeneratorMetadataInterface $metadata): void
        {
            if (!$metadata instanceof MapperMetadata) {
                return;
            }

            $metadata->forMember('yearOfBirth', function (User $user) {
                return ((int) date('Y')) - ((int) $user->age);
            });
        }
    }

    class YearOfBirthTransformer
    {
    }
}

if (Kernel::MAJOR_VERSION < 6) {
    class IdNameConverter implements AdvancedNameConverterInterface
    {
        public function normalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('id' === $propertyName) {
                return '@id';
            }

            return $propertyName;
        }

        public function denormalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('@id' === $propertyName) {
                return 'id';
            }

            return $propertyName;
        }
    }
} else {
    class IdNameConverter implements AdvancedNameConverterInterface
    {
        public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('id' === $propertyName) {
                return '@id';
            }

            return $propertyName;
        }

        public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
        {
            if ('@id' === $propertyName) {
                return 'id';
            }

            return $propertyName;
        }
    }
}
