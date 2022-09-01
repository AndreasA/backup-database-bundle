<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('andreasa_backup_database');

        // @formatter:off
        $rootNode = $treeBuilder->getRootNode();

        // prettier-ignore
        $rootNode
            ->children()
                ->scalarNode('url')->defaultNull()->end()
            ->append($this->createMysqlSection())
            ->end();
        // @formatter:on

        return $treeBuilder;
    }

    protected function createMysqlSection(): ArrayNodeDefinition
    {
        $rootNode = (new TreeBuilder('mysql'))->getRootNode();

        // @formatter:off
        $rootNode
            ->children()
                ->arrayNode('ignored_tables')
                    ->defaultValue([])
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('options')
                    ->defaultValue([])
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('platform_specific_options')
                    ->defaultValue([])
                    ->scalarPrototype()->end()
                ->end()
            ->end();
        // @formatter:on

        return $rootNode;
    }
}
