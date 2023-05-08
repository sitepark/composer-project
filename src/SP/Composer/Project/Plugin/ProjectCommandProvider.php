<?php

namespace SP\Composer\Project\Plugin;

class ProjectCommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return [
            new \SP\Composer\Project\Plugin\Commands\ReleaseCommand(),
            new \SP\Composer\Project\Plugin\Commands\VersionCommand(),
            new \SP\Composer\Project\Plugin\Commands\ReleaseVersionCommand(),
            new \SP\Composer\Project\Plugin\Commands\StartHotfixCommand(),
            new \SP\Composer\Project\Plugin\Commands\VerifyReleaseCommand(),
        ];
    }
}
