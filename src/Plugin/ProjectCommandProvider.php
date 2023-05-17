<?php

declare(strict_types=1);

namespace SP\Composer\Project\Plugin;

use SP\Composer\Project\Plugin\Commands\ReleaseCommand;
use SP\Composer\Project\Plugin\Commands\ReleaseVersionCommand;
use SP\Composer\Project\Plugin\Commands\StartHotfixCommand;
use SP\Composer\Project\Plugin\Commands\VerifyReleaseCommand;
use SP\Composer\Project\Plugin\Commands\VersionCommand;

class ProjectCommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return [
            new ReleaseCommand(),
            new VersionCommand(),
            new ReleaseVersionCommand(),
            new StartHotfixCommand(),
            new VerifyReleaseCommand(),
        ];
    }
}
