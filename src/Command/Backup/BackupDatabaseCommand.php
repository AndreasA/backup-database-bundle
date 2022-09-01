<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\Command\Backup;

use AndreasA\BackupDatabaseBundle\Handler\BackupDatabaseHandlerChain;
use DateTime;
use DateTimeInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Throwable;
use UnexpectedValueException;

class BackupDatabaseCommand extends Command
{
    protected static $defaultName = 'andreas-a:backup:database';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * @var BackupDatabaseHandlerChain
     */
    private $handler;

    public function __construct(
        BackupDatabaseHandlerChain $handler,
        Filesystem $filesystem,
        string $targetDirectory,
        string $name = null
    ) {
        parent::__construct($name);

        $this->filesystem = $filesystem;
        $this->handler = $handler;
        $this->targetDirectory = rtrim($targetDirectory, '/');
    }

    protected function configure()
    {
        $this->setDescription('Creates a database backup.');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Starting database backup.');

        $currentDateTime = (new DateTime())->format(DateTimeInterface::RFC3339_EXTENDED);

        $backupPath = sprintf('%1$s/db_%2$s.sql.bz2', $this->targetDirectory, $currentDateTime);

        $this->filesystem->mkdir($this->targetDirectory);

        $namedPipe = $this->createNamedPipe();
        $compressProcess = null;

        try {
            $compressProcess = $this->createCompressProcess($namedPipe, $backupPath);
            $compressProcess->start();

            $dumpProcess = $this->handler->createDumpProcess($namedPipe);
            $dumpProcess->run();

            if (false === $dumpProcess->isSuccessful()) {
                throw new RuntimeException($dumpProcess->getErrorOutput());
            }

            $compressProcess->wait();
        } catch (Throwable $throwable) {
            if (null !== $compressProcess && $compressProcess->isRunning()) {
                $compressProcess->stop(0);
            }

            // Remove the empty file created by the command's output pipe.
            $this->filesystem->remove($backupPath);

            throw $throwable;
        } finally {
            // Remove potentially created named pipe.
            $this->filesystem->remove($namedPipe);
        }

        $errorOutput = $dumpProcess->getErrorOutput();
        $io->getErrorStyle()->write($errorOutput);

        $isFailure =
            false === $dumpProcess->isSuccessful() ||
            false === $compressProcess->isSuccessful() ||
            $this->handler->hasError($dumpProcess, $errorOutput);

        if ($isFailure) {
            // Remove the empty file created by the command's output pipe.
            $this->filesystem->remove($backupPath);

            $io->getErrorStyle()->newLine();
            $io->error('Failed to backup database.');

            return 1;
        }

        $io->success('Finished database backup.');

        return 0;
    }

    private function createNamedPipe(): string
    {
        // Not using "tempnam" as that function also creates the file but the file is created by "mkfifo".
        $namedPipe = sprintf('%1$s/db_fifo_%2$s', $this->targetDirectory, bin2hex(random_bytes(32)));

        $process = new Process(['mkfifo', $namedPipe]);

        $process->setPty(false);
        $process->setTimeout(null);
        $process->setTty(false);

        $process->run();

        if (false === $process->isSuccessful()) {
            // Remove potentially created named pipe.
            $this->filesystem->remove($namedPipe);

            throw new UnexpectedValueException('Failed creating named pipe.');
        }

        return $namedPipe;
    }

    private function createCompressProcess(string $namedPipe, string $backupPath): Process
    {
        $process = Process::fromShellCommandline(implode(' ', $this->getCompressCommandLineParts()));

        $process->setPty(false);
        $process->setTimeout(null);
        $process->setTty(false);

        $process->setEnv([
            'DB_CONTENT_PIPE' => $namedPipe,
            'DB_BACKUP_PATH' => $backupPath,
        ]);

        return $process;
    }

    private function getCompressCommandLineParts(): array
    {
        return ['bzip2', '<', '"$DB_CONTENT_PIPE"', '>', '"$DB_BACKUP_PATH"'];
    }
}
