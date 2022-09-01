<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\Handler;

use Symfony\Component\Process\Process;

interface BackupDatabaseHandlerInterface
{
    public const SERVICE_TAG = 'andreas_a.backup.database_handler';

    public function createDumpProcess(string $targetFile, array $params): Process;

    public function supports(string $scheme): bool;
}
