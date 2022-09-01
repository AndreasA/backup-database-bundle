<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\DependencyInjection\Compiler;

use AndreasA\BackupDatabaseBundle\Handler\BackupDatabaseHandlerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BackupDatabaseHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->has('andreas_a.backup_database.handler.backup_database_handler_chain')) {
            return;
        }

        $definition = $container->findDefinition('andreas_a.backup_database.handler.backup_database_handler_chain');
        $services = $container->findTaggedServiceIds(BackupDatabaseHandlerInterface::SERVICE_TAG);

        foreach ($services as $id => $_) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
