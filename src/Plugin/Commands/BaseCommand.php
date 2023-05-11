<?php

namespace SP\Composer\Project\Plugin\Commands;

use SP\Composer\Project\ReleaseManagement;
use SP\Composer\Project\Project;
use SP\Composer\Project\Git\StandardGitProvider;

abstract class BaseCommand extends \Composer\Command\BaseCommand
{
    protected function getProject(): Project
    {
        //$package = $this->getComposer(true)->getPackage();
        $package = $this->requireComposer(true)->getPackage();
        $git = new StandardGitProvider();
        return new Project($package, $git);
    }

    protected function getReleaseManagement(): ReleaseManagement
    {
        return new ReleaseManagement($this->getProject());
    }
}
