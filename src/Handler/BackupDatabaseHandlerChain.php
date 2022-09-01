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
        $params = $this->getParams();
        $scheme = $this->getScheme($params);

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

    public function hasError(Process $process, string $errorOutput): bool
    {
        $scheme = $this->getScheme($this->getParams());

        foreach ($this->handlers as $handler) {
            if (false === $handler->supports($scheme)) {
                continue;
            }

            return $handler->hasError($process, $errorOutput);
        }

        throw new RuntimeException(sprintf('Scheme "%1$s" is not supported.', $scheme));
    }

    private function getParams(): array
    {
        return parse_url($this->databaseUrl);
    }

    private function getScheme(array $params): string
    {
        $scheme = $params['scheme'] ?? null;

        if (false === is_string($scheme) || '' === $scheme) {
            throw new UnexpectedValueException('Database URL and/or scheme is invalid or missing.');
        }

        return $scheme;
    }
}
