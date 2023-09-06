<?php

declare(strict_types=1);

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Input\InputArgument;
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
        $this->addArgument('tag', InputArgument::REQUIRED, 'Git tag from which the hotfix should be created.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tag = $input->getArgument('tag');
        $version = $this->getReleaseManagement()->startHotfix($tag);

        $io = new SymfonyStyle($input, $output);
        $io->success("Hotfix-Branch created for version $version");

        return 0;
    }
}
