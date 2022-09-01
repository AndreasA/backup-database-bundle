<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle;

use AndreasA\BackupDatabaseBundle\DependencyInjection\Compiler\BackupDatabaseHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AndreasABackupDatabaseBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BackupDatabaseHandlerPass());
    }
}
