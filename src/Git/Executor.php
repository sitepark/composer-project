<?php

declare(strict_types=1);

namespace SP\Composer\Project\Git;

class Executor
{
    private string $workdir;

    public function __construct(string $workdir = '.')
    {
        $this->workdir = $workdir;
    }

    /**
     * @return array<string>
     */
    public function exec(string $command): array
    {

        $descriptorspec = array(
            0 => array("pipe", "r"), // STDIN
            1 => array("pipe", "w"), // STDOUT
            2 => array("pipe", "w"), // STDERR
        );

        $env = null;

        $proc = proc_open($command, $descriptorspec, $pipes, $this->workdir, $env);
        if (is_resource($proc)) {
            $stdout = trim(stream_get_contents($pipes[1]) ?: "");
            $stderr = trim(stream_get_contents($pipes[2]) ?: "");
            $exitCode = proc_close($proc);
            if ($exitCode !== 0) {
                throw new \RuntimeException(
                    "stdout:\n" . $stdout . "\n" .
                    "stderr:\n" . $stderr . "\n"
                );
            }

            if (empty($stdout)) {
                return [];
            }
            return explode("\n", $stdout);
        }
        return [];
    }
}
