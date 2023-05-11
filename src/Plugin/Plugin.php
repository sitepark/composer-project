<?php

namespace SP\Composer\Project\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\Capability\CommandProvider;

class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        /**
         * Not needed yet
         */
        //$composer->getInstallationManager()->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        /**
         * Not needed yet
         */
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        /**
         * Not needed yet
         */
    }

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => ProjectCommandProvider::class
        ];
    }
}
