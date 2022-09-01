<?php declare(strict_types=1);

namespace AndreasA\BackupDatabaseBundle\Handler;

use Symfony\Component\Process\Process;

class MysqlBackupDatabaseHandler implements BackupDatabaseHandlerInterface
{
    /**
     * @var array
     */
    private $ignoredTables;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $platformSpecificOptions;

    public function __construct(array $ignoredTables, array $options, array $platformSpecificOptions)
    {
        $this->ignoredTables = $ignoredTables;
        $this->options = $options;
        $this->platformSpecificOptions = $platformSpecificOptions;
    }

    public function createDumpProcess(string $targetFile, array $params): Process
    {
        $process = Process::fromShellCommandline(implode(' ', $this->getDumpCommandLineParts()));

        $user = rawurldecode($params['user'] ?? '');
        $password = rawurldecode($params['pass'] ?? '');

        $process->setInput($this->getOptionsFileContent($user, $password));
        $name = ltrim($params['path'] ?? '', '/');

        $env = [
            'DB_HOST' => $params['host'] ?? '',
            'DB_NAME' => $name,
            'DB_PORT' => $params['port'] ?? '3306',
            'DB_TARGET_FILE' => $targetFile,
        ];

        foreach ($this->ignoredTables as $index => $table) {
            $env[sprintf('DB_IGNORED_TABLE_%1$u', $index)] = sprintf('%1$s.%2$s', $name, $table);
        }

        $process->setEnv($env);

        return $process;
    }

    public function supports(string $scheme): bool
    {
        return 'mysql' === $scheme;
    }

    private function getIgnoredTablesCommandLineParts(): array
    {
        return array_map(static function (int $key) {
            return sprintf('--ignore-table="$DB_IGNORED_TABLE_%1$u"', $key);
        }, array_keys($this->ignoredTables));
    }

    private function getDumpCommandLineParts(): array
    {
        return array_merge(
            ['mysqldump', '--defaults-file=/dev/stdin'],
            $this->getIgnoredTablesCommandLineParts(),
            ['-h', '"$DB_HOST"', '-P', '"$DB_PORT"', '"$DB_NAME"', '>', '"$DB_CONTENT_PIPE"'],
        );
    }

    /**
     * Using an options file to provide the username and password securely
     * and potential additional options.
     */
    private function getOptionsFileContent(string $user, string $password): string
    {
        return sprintf(
            <<<'CONTENT'
            [client]
            %1$s
            %2$s
            password=%3$s
            user=%4$s
            CONTENT
            ,
            implode(PHP_EOL, $this->options),
            implode(PHP_EOL, $this->getPlatformSpecificOptions()),
            $password,
            $user,
        );
    }

    private function getPlatformSpecificOptions(): array
    {
        $process = Process::fromShellCommandline('mysqldump --defaults-file=/dev/stdin --help');

        $process->setPty(false);
        $process->setTimeout(null);
        $process->setTty(false);

        $template = <<<'CONTENT'
        [client]
        %1$s
        CONTENT;

        $platformOptions = [];

        foreach ($this->platformSpecificOptions as $option) {
            $process->setInput(sprintf($template, $option));
            $process->run();

            if (false === $process->isSuccessful()) {
                continue;
            }

            $platformOptions[] = $option;
        }

        return $platformOptions;
    }
}
