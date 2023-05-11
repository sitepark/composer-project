<?php

namespace SP\Composer\Project\Plugin\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReleaseVersionCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('project:releaseVersion');
        $this->setDescription('Liefert die nächste Release-Version zurück');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $releaseVersion = $this->getProject()->getReleaseVersion();
        echo $releaseVersion . "\n";
        return 0;
    }
}
