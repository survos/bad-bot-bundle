<?php declare(strict_types=1);

namespace Survos\BadBotBundle;

use Survos\BadBotBundle\EventListener\BeforeRequestListener;
use Survos\BadBotBundle\EventListener\ExceptionListener;
use Survos\BadBotBundle\Service\BotService;
use Survos\KeyValueBundle\Type\DefaultType;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosBadBotBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Inspector will be injected automatically, but we could spell it out.
        array_map(fn($class) => $builder->autowire($class)
            ->setPublic(true)
            ->setAutoconfigured(true)
            ->setAutowired(true),
            [
                BotService::class,
                ExceptionListener::class,
                BeforeRequestListener::class]);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
            ->integerNode('block_time')->info('number of seconds to block this IP, -1 is permanent')->defaultValue(300)->end()
            ->scalarNode('ip_list_name')->info('ip list name in kv-bundle')->defaultValue('blocked_ips')->end()
            ->scalarNode('probe_paths_name')->info('probe path patterns list name')->defaultValue('probes')->end()
            ->end();

    }


}
