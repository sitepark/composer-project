<?php

declare(strict_types=1);

namespace SP\Composer\Project\Plugin\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VersionCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('project:version');
        $this->setDescription('Returns the current version in Composer format (e.g.: dev-develop)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->getProject();

        if ($project->isDev()) {
            echo 'dev-' . $project->getBranch() . "\n";
        } else {
            echo $project->getReleaseVersion() . "\n";
            ;
        }

        return 0;
    }
}
