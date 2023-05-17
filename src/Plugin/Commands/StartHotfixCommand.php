<?php

declare(strict_types=1);

namespace SP\Composer\Project\Plugin\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartHotfixCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('project:startHotfix');
        $this->setDescription('Creates a hotfix branch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $this->getReleaseManagement()->startHotfix();

        $io = new SymfonyStyle($input, $output);
        $io->success("Hotfix-Branch created for version $version");

        return 0;
    }
}
