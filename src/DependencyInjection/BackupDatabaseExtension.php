<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class BackupDatabaseExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $confDir = __DIR__ . '/../../config';

        $locator = new FileLocator($confDir);
        $loader = new YamlFileLoader($container, $locator);

        $loader->load(sprintf('%1$s/%2$s.yaml', $confDir, 'services'), 'yaml');

        $this->setDatabaseUrl($mergedConfig, $container);
        $this->setMysqlArguments($mergedConfig, $container);
    }

    private function setDatabaseUrl(array $mergedConfig, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('andreasa.backup_database.handler.backup_database_handler_chain');

        $definition->replaceArgument('$databaseUrl', $mergedConfig['database_url'] ?? '');
        $definition->replaceArgument('$targetDirectory', $mergedConfig['target_directory'] ?? '');
    }

    private function setMysqlArguments(array $mergedConfig, ContainerBuilder $container): void
    {
        $mysqlConfig = $mergedConfig['mysql'] ?? [];

        $definition = $container->getDefinition('andreasa.backup_database.handler.mysql_backup_database_handler');

        $definition->replaceArgument('$ignoredTables', $mysqlConfig['ignored_tables'] ?? []);
        $definition->replaceArgument('$options', $mysqlConfig['options'] ?? []);
        $definition->replaceArgument('$platformSpecificOptions', $mysqlConfig['platform_specific_options'] ?? []);
    }
}
