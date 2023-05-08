<?php

namespace SP\Composer\Project\Plugin\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VerifyReleaseCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('project:verifyRelease');
        $this->setDescription('Checks if all conditions are met to create a release.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->getReleaseManagement()->verifyRelease();

        $io = new SymfonyStyle($input, $output);
        $io->success('Project is ready to release');

        return 0;
    }
}
