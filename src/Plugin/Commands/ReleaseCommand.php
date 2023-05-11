<?php

namespace SP\Composer\Project\Plugin\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReleaseCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('project:release');
        $this->setDescription('Creates a release');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $releasedVersion = $this->getReleaseManagement()->release();

        $io = new SymfonyStyle($input, $output);
        $io->success("Release $releasedVersion was created");

        return 0;
    }
}
