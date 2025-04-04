<?php

declare(strict_types=1);

namespace SP\Composer\Project\Plugin\Commands;

use SP\Composer\Project\Git\Executor;
use SP\Composer\Project\ReleaseManagement;
use SP\Composer\Project\Project;
use SP\Composer\Project\Git\StandardGitProvider;

abstract class BaseCommand extends \Composer\Command\BaseCommand
{
    private ?Project $project = null;

    private ?ReleaseManagement $releaseManagement = null;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    public function setReleaseManagement(ReleaseManagement $releaseManagement): void
    {
        $this->releaseManagement = $releaseManagement;
    }

    public function getProject(): Project
    {
        if ($this->project === null) {
            $this->project = $this->createProject();
            return $this->project;
        }
        return $this->project;
    }

    public function getReleaseManagement(): ReleaseManagement
    {
        if ($this->releaseManagement === null) {
            $this->releaseManagement = $this->createReleaseManagement();
            return $this->releaseManagement;
        }
        return $this->releaseManagement;
    }

    private function createProject(): Project
    {
        //$package = $this->getComposer(true)->getPackage();
        $package = $this->requireComposer(true)->getPackage();
        $git = new StandardGitProvider(new Executor());
        return new Project($package, $git);
    }

    private function createReleaseManagement(): ReleaseManagement
    {
        return new ReleaseManagement($this->getProject(), new Executor());
    }
}
