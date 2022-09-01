<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\Handler;

use RuntimeException;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class BackupDatabaseHandlerChain
{
    private $databaseUrl;

    /**
     * @var BackupDatabaseHandlerInterface[]
     */
    private $handlers = [];

    public function __construct(string $databaseUrl)
    {
        $this->databaseUrl = $databaseUrl;
    }

    public function addHandler(BackupDatabaseHandlerInterface $handler): void
    {
        $this->handlers[get_class($handler)] = $handler;
    }

    public function createDumpProcess(string $targetFile): Process
    {
        $params = parse_url($this->databaseUrl);

        $scheme = $params['scheme'] ?? null;

        if (false === is_string($scheme) || '' === $scheme) {
            throw new UnexpectedValueException('Database URL and/or scheme is invalid or missing.');
        }

        foreach ($this->handlers as $handler) {
            if (false === $handler->supports($scheme)) {
                continue;
            }

            $process = $handler->createDumpProcess($targetFile, $params);

            $process->setPty(false);
            $process->setTimeout(null);
            $process->setTty(false);

            return $process;
        }

        throw new RuntimeException(sprintf('Scheme "%1$s" is not supported.', $scheme));
    }
}
