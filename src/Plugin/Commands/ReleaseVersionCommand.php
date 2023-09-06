<?php

declare(strict_types=1);

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
        $releaseVersion = $this->getProject()->getNextReleaseVersion();
        echo $releaseVersion . "\n";
        return 0;
    }
}
